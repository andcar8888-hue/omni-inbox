<?php

namespace App\Services\Channels;

/**
 * Contract every platform adapter (Telegram, WhatsApp, Messenger, Instagram,
 * TikTok) must implement. Per CLAUDE.md's folder-ownership rule, all adapters
 * live in App\Services\Channels and expose the same two operations so that
 * ConversationService (outbound) and the webhook layer (inbound) can treat any
 * platform uniformly — no platform-specific branching leaks into controllers.
 *
 * Adding a new platform = writing one class that implements this interface and
 * registering it in ChannelResolver. Nothing else in the app changes.
 */
interface ChannelAdapterInterface
{
    /**
     * Deliver an outbound reply to the real platform recipient for the given
     * conversation, and persist the outbound message row.
     *
     * The adapter is responsible for:
     *   - resolving the conversation -> contact -> external recipient id,
     *   - calling the platform's send API,
     *   - persisting the messages row (direction=outbound),
     *   - setting status to 'sent' on success or 'failed' on any platform error.
     *
     * Platform/transport failures MUST NOT bubble up as unhandled exceptions;
     * they are recorded on the persisted message as status='failed' so the HTTP
     * layer still returns the created row and the UI can show a failed send.
     *
     * @return array<string, mixed> the created message row (status reflects outcome)
     */
    public function sendMessage(int $conversationId, string $body): array;

    /**
     * Normalize a raw inbound webhook payload into the common Message DTO, or
     * return null if this payload is not a message this adapter handles (e.g. a
     * non-text update, an edited-message event, a delivery receipt).
     *
     * The DTO shape (all values already trimmed/plain scalars):
     *   [
     *     'external_contact_id' => string,  // platform's chat/user id
     *     'external_message_id' => string,  // platform's own id, for dedupe
     *     'contact_display_name'=> ?string, // best-effort human name
     *     'body'                => string,  // message text
     *     'sent_at'             => string,  // UTC 'Y-m-d H:i:s'
     *   ]
     *
     * @param array<string, mixed> $payload
     *
     * @return array{
     *     external_contact_id: string,
     *     external_message_id: string,
     *     contact_display_name: ?string,
     *     body: string,
     *     sent_at: string
     * }|null
     */
    public function normalizeIncoming(array $payload): ?array;
}
