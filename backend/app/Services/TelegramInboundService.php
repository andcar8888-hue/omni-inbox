<?php

namespace App\Services;

use App\Models\ChannelModel;
use App\Models\ContactModel;
use App\Models\ConversationModel;
use App\Models\MessageModel;
use App\Services\Channels\TelegramChannel;
use App\Services\Exceptions\NoTelegramChannelException;

/**
 * Business logic for an inbound Telegram webhook update.
 *
 * The controller is thin: it verifies the secret-token header and hands the raw
 * decoded payload here. This service:
 *   1. normalizes the update via TelegramChannel::normalizeIncoming(),
 *   2. resolves the owning channel (single-tenant dev: the one telegram channel),
 *   3. dedupes on a channel-scoped key BEFORE inserting (webhook idempotency),
 *   4. find-or-creates the contact and conversation,
 *   5. inserts the inbound message and bumps last_message_at.
 *
 * Domain rules honoured (CLAUDE.md):
 *   - conversation unique per (channel_id, external_contact_id),
 *   - inbound messages idempotent against retries,
 *   - timestamps in UTC.
 */
class TelegramInboundService
{
    private TelegramChannel $adapter;
    private ChannelModel $channels;
    private ContactModel $contacts;
    private ConversationModel $conversations;
    private MessageModel $messages;

    public function __construct(
        ?TelegramChannel $adapter = null,
        ?ChannelModel $channels = null,
        ?ContactModel $contacts = null,
        ?ConversationModel $conversations = null,
        ?MessageModel $messages = null
    ) {
        $this->adapter       = $adapter ?? new TelegramChannel();
        $this->channels      = $channels ?? new ChannelModel();
        $this->contacts      = $contacts ?? new ContactModel();
        $this->conversations = $conversations ?? new ConversationModel();
        $this->messages      = $messages ?? new MessageModel();
    }

    /**
     * Result codes so the controller can respond appropriately without knowing
     * business logic:
     *   'ignored'   - not a text message we handle (200, nothing stored)
     *   'duplicate' - already processed this update (200, nothing stored)
     *   'stored'    - new inbound message persisted (200)
     */
    public const RESULT_IGNORED   = 'ignored';
    public const RESULT_DUPLICATE = 'duplicate';
    public const RESULT_STORED    = 'stored';

    /**
     * Process one Telegram Update payload.
     *
     * @param array<string, mixed> $payload
     *
     * @return array{result: string, message_id?: int}
     *
     * @throws NoTelegramChannelException when no telegram channel row exists
     *         (a real setup problem the controller surfaces / logs).
     */
    public function handleUpdate(array $payload): array
    {
        $dto = $this->adapter->normalizeIncoming($payload);
        if ($dto === null) {
            // Non-text / non-message update. Nothing to store; the webhook still
            // 200s so Telegram stops retrying.
            return ['result' => self::RESULT_IGNORED];
        }

        // Which business owns this bot? Single-tenant dev: exactly one telegram
        // channel is expected. If none exists this is a setup problem (no
        // "connect channel" onboarding yet); we throw so the controller can log
        // it loudly. See ADR note in the controller for the 200-anyway rationale.
        $channel = $this->channels
            ->where('platform', TelegramChannel::PLATFORM)
            ->orderBy('id', 'ASC')
            ->first();

        if ($channel === null) {
            throw new NoTelegramChannelException(
                'No telegram channel is configured; cannot attribute inbound message.'
            );
        }

        $channelId = (int) $channel['id'];

        // Idempotency: build a globally-unique, channel-scoped external id. The
        // messages.external_message_id UNIQUE key is single-column/global, and a
        // bare Telegram update_id could collide across bots/channels — so prefix
        // with the channel id: "tg:{channel_id}:{update_id}". Check for an
        // existing row BEFORE inserting rather than relying on the DB error.
        $externalMessageId = 'tg:' . $channelId . ':' . $dto['external_message_id'];

        $existing = $this->messages
            ->where('external_message_id', $externalMessageId)
            ->first();

        if ($existing !== null) {
            return ['result' => self::RESULT_DUPLICATE, 'message_id' => (int) $existing['id']];
        }

        $contactId      = $this->findOrCreateContact($channelId, $dto['external_contact_id'], $dto['contact_display_name']);
        $conversationId = $this->findOrCreateConversation($channelId, $contactId, $dto['sent_at']);

        $messageId = $this->messages->insert([
            'conversation_id'     => $conversationId,
            'direction'           => 'inbound',
            'sender_user_id'      => null,
            'external_message_id' => $externalMessageId,
            'body'                => $dto['body'],
            'status'              => 'delivered',
            'created_at'          => $dto['sent_at'],
        ], true);

        // Every stored inbound message is unread until an agent opens the thread.
        // Bump last_message_at (latest activity) and increment unread_count in a
        // single UPDATE. The increment is an atomic SQL expression
        // (`unread_count + 1`) rather than a read-then-write so two concurrent
        // webhook deliveries on the same conversation can't clobber each other's
        // count. This covers BOTH a freshly-created conversation (which starts at
        // 0, so this lands it at 1 — the first message is itself unread) and reuse
        // of an existing conversation (2nd/3rd inbound message -> 2, 3, ...).
        // Use the raw query builder (not the Model's update()) so the
        // `unread_count + 1` expression is emitted verbatim as a SQL increment
        // rather than being escaped/validated as a literal value by the Model.
        $this->conversations->builder()
            ->set('last_message_at', $dto['sent_at'])
            ->set('unread_count', 'unread_count + 1', false)
            ->where('id', $conversationId)
            ->update();

        return ['result' => self::RESULT_STORED, 'message_id' => (int) $messageId];
    }

    /**
     * Find-or-create a contact for (channel_id, external_contact_id). Refreshes a
     * stale display_name when Telegram sends a better one.
     */
    private function findOrCreateContact(int $channelId, string $externalContactId, ?string $displayName): int
    {
        $existing = $this->contacts
            ->where('channel_id', $channelId)
            ->where('external_contact_id', $externalContactId)
            ->first();

        if ($existing !== null) {
            $id = (int) $existing['id'];

            // Backfill a name if we didn't have one and now do.
            if (($existing['display_name'] ?? null) === null && $displayName !== null) {
                $this->contacts->update($id, ['display_name' => $displayName]);
            }

            return $id;
        }

        return (int) $this->contacts->insert([
            'channel_id'          => $channelId,
            'external_contact_id' => $externalContactId,
            'display_name'        => $displayName,
            'created_at'          => gmdate('Y-m-d H:i:s'),
        ], true);
    }

    /**
     * Find-or-create the conversation for (channel_id, contact_id) — the domain's
     * uniqueness rule. A conversation is unique per channel+contact.
     */
    private function findOrCreateConversation(int $channelId, int $contactId, string $lastMessageAt): int
    {
        $existing = $this->conversations
            ->where('channel_id', $channelId)
            ->where('contact_id', $contactId)
            ->first();

        if ($existing !== null) {
            return (int) $existing['id'];
        }

        // unread_count starts at 0 here; handleUpdate() performs the atomic
        // `unread_count + 1` bump right after inserting the message, so a brand-new
        // conversation's first (unread) message correctly lands it at 1. Seeding it
        // at 0 keeps a single, race-safe increment path for both new and reused
        // conversations rather than special-casing the first message.
        return (int) $this->conversations->insert([
            'channel_id'      => $channelId,
            'contact_id'      => $contactId,
            'assigned_user_id' => null,
            'status'          => 'open',
            'last_message_at' => $lastMessageAt,
            'unread_count'    => 0,
        ], true);
    }
}
