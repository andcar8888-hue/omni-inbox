<?php

namespace App\Services\Channels;

use App\Models\ContactModel;
use App\Models\ConversationModel;
use App\Models\MessageModel;
use Throwable;

/**
 * Telegram Bot API adapter — the first real platform integration (Phase 5).
 *
 * Outbound: calls https://api.telegram.org/bot<token>/sendMessage with the
 * contact's Telegram chat id and the reply text, using CI4's curlrequest
 * service (the idiomatic CI4 way to make outbound HTTP; we do not hand-roll cURL).
 *
 * Inbound normalization: turns a Telegram `Update` containing a private-chat text
 * `message` into the common Message DTO. Non-text / non-message updates return
 * null so the webhook can 200-and-ignore them.
 *
 * Failure policy (CLAUDE.md / interface contract): a network error, a non-200
 * HTTP response, or Telegram's own `{"ok": false}` error shape does NOT throw up
 * to the HTTP layer. The message is still persisted, but with status='failed'.
 */
class TelegramChannel implements ChannelAdapterInterface
{
    public const PLATFORM = 'telegram';

    private const API_BASE = 'https://api.telegram.org';

    private ContactModel $contacts;
    private ConversationModel $conversations;
    private MessageModel $messages;

    public function __construct(
        ?ContactModel $contacts = null,
        ?ConversationModel $conversations = null,
        ?MessageModel $messages = null
    ) {
        $this->contacts      = $contacts ?? new ContactModel();
        $this->conversations = $conversations ?? new ConversationModel();
        $this->messages      = $messages ?? new MessageModel();
    }

    /**
     * {@inheritDoc}
     */
    public function sendMessage(int $conversationId, string $body): array
    {
        $conversation = $this->conversations->find($conversationId);
        if ($conversation === null) {
            // The orchestrator (ConversationService) has already verified
            // ownership/existence before calling us, so this is defensive only.
            throw new \RuntimeException('Conversation not found for Telegram send.');
        }

        $contact = $this->contacts->find((int) $conversation['contact_id']);
        if ($contact === null) {
            throw new \RuntimeException('Contact not found for Telegram send.');
        }

        $now = gmdate('Y-m-d H:i:s');
        $chatId = (string) $contact['external_contact_id'];

        // Attempt delivery first, but never let a transport error escape: the
        // outcome is folded into the persisted message's status.
        $delivered = $this->deliverToTelegram($chatId, $body);

        $data = [
            'conversation_id' => $conversationId,
            'direction'       => 'outbound',
            'sender_user_id'  => null,
            'body'            => $body,
            'status'          => $delivered ? 'sent' : 'failed',
            'created_at'      => $now,
        ];

        $messageId = $this->messages->insert($data, true);

        return $this->messages->find($messageId);
    }

    /**
     * Perform the outbound HTTP call to Telegram. Returns true only if Telegram
     * accepted the message (HTTP 200 AND body `ok === true`). All error modes
     * (missing token, network exception, non-200, `ok: false`) return false and
     * are logged without dumping the raw payload.
     */
    private function deliverToTelegram(string $chatId, string $text): bool
    {
        $token = (string) env('TELEGRAM_BOT_TOKEN');
        if ($token === '' || $token === 'REPLACE_WITH_REAL_BOT_TOKEN') {
            log_message('error', 'TelegramChannel: TELEGRAM_BOT_TOKEN is not configured; marking send failed.');

            return false;
        }

        $url = self::API_BASE . '/bot' . $token . '/sendMessage';

        try {
            $client = service('curlrequest', [
                'timeout'     => 10,
                'http_errors' => false, // handle non-2xx ourselves rather than throw
            ]);

            $response = $client->post($url, [
                'json' => [
                    'chat_id' => $chatId,
                    'text'    => $text,
                ],
            ]);
        } catch (Throwable $e) {
            // Network/timeout/DNS failure. Do not leak the token-bearing URL.
            log_message('error', 'TelegramChannel: sendMessage transport error: ' . $e->getMessage());

            return false;
        }

        $status = $response->getStatusCode();
        if ($status !== 200) {
            log_message('error', 'TelegramChannel: sendMessage non-200 response (HTTP ' . $status . ').');

            return false;
        }

        $decoded = json_decode((string) $response->getBody(), true);
        if (! is_array($decoded) || ($decoded['ok'] ?? false) !== true) {
            $description = is_array($decoded) ? (string) ($decoded['description'] ?? 'unknown') : 'unparseable body';
            log_message('error', 'TelegramChannel: Telegram API returned ok=false: ' . $description);

            return false;
        }

        return true;
    }

    /**
     * {@inheritDoc}
     *
     * Telegram `Update` -> Message DTO. We only care about `message` updates that
     * carry a `text` (skip edited_message, channel_post, callbacks, stickers,
     * photos-without-caption, etc. for this phase). Real field names per the
     * Telegram Bot API `Update` and `Message` objects.
     */
    public function normalizeIncoming(array $payload): ?array
    {
        $message = $payload['message'] ?? null;
        if (! is_array($message)) {
            return null; // edited_message, channel_post, callback_query, ... — ignore.
        }

        $text = $message['text'] ?? null;
        if (! is_string($text) || $text === '') {
            return null; // non-text message (photo, sticker, location, ...) — ignore.
        }

        $chat = $message['chat'] ?? null;
        if (! is_array($chat) || ! isset($chat['id'])) {
            return null; // malformed — no chat to attribute this to.
        }

        // chat.id is the id we reply to; it is also the stable per-contact key
        // for a private chat.
        $externalContactId = (string) $chat['id'];

        // Telegram's update_id is unique per bot; message_id is unique per chat.
        // The webhook prefixes this with the channel id (see TelegramInboundService)
        // to build a globally-unique external_message_id. Here we surface the raw
        // update_id so the caller controls the prefixing scheme.
        $updateId = $payload['update_id'] ?? null;
        if ($updateId === null) {
            return null; // no dedupe key — cannot safely persist.
        }

        return [
            'external_contact_id'  => $externalContactId,
            'external_message_id'  => (string) $updateId,
            'contact_display_name' => $this->displayName($message['from'] ?? null, $chat),
            'body'                 => $text,
            'sent_at'              => $this->sentAt($message['date'] ?? null),
        ];
    }

    /**
     * Best-effort human name from the sender `from` object, falling back to the
     * chat's own name fields. Telegram `from`/`chat`: first_name, last_name,
     * username (all optional).
     *
     * @param array<string, mixed>|null $from
     * @param array<string, mixed>      $chat
     */
    private function displayName($from, array $chat): ?string
    {
        $source = is_array($from) ? $from : $chat;

        $first = isset($source['first_name']) ? trim((string) $source['first_name']) : '';
        $last  = isset($source['last_name']) ? trim((string) $source['last_name']) : '';

        $full = trim($first . ' ' . $last);
        if ($full !== '') {
            return $full;
        }

        if (isset($source['username']) && (string) $source['username'] !== '') {
            return '@' . (string) $source['username'];
        }

        return null;
    }

    /**
     * Telegram `message.date` is a Unix timestamp (UTC seconds). Convert to the
     * app's UTC 'Y-m-d H:i:s'. Falls back to now if absent/invalid.
     *
     * @param mixed $date
     */
    private function sentAt($date): string
    {
        if (is_int($date) || (is_string($date) && ctype_digit($date))) {
            return gmdate('Y-m-d H:i:s', (int) $date);
        }

        return gmdate('Y-m-d H:i:s');
    }
}
