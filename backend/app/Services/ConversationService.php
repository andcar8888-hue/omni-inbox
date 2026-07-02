<?php

namespace App\Services;

use App\Models\ChannelModel;
use App\Models\ConversationModel;
use App\Models\MessageModel;
use App\Services\Channels\ChannelResolver;
use App\Services\Exceptions\ConversationNotFoundException;

/**
 * Business logic for conversations and their messages, always scoped to a
 * business_id so one tenant can never read or write another tenant's data.
 *
 * The tenant boundary reaches conversations/messages through
 * channels.business_id -> conversations.channel_id -> messages.conversation_id.
 */
class ConversationService
{
    private ConversationModel $conversations;
    private MessageModel $messages;
    private ChannelModel $channels;
    private ChannelResolver $channelResolver;

    public function __construct(
        ?ConversationModel $conversations = null,
        ?MessageModel $messages = null,
        ?ChannelModel $channels = null,
        ?ChannelResolver $channelResolver = null
    ) {
        $this->conversations   = $conversations ?? new ConversationModel();
        $this->messages        = $messages ?? new MessageModel();
        $this->channels        = $channels ?? new ChannelModel();
        $this->channelResolver = $channelResolver ?? new ChannelResolver();
    }

    /**
     * List all conversations for a business, newest activity first.
     *
     * Single joined query (conversations -> channels), filtered on
     * channels.business_id and ordered by conversations.last_message_at DESC.
     * The ORDER BY + channel filter are served by idx_conv_channel_last_message.
     * No N+1: contact/channel display fields are pulled in the same SELECT.
     *
     * @return list<array<string, mixed>>
     */
    public function listForBusiness(int $businessId): array
    {
        return $this->conversations
            ->select(
                'conversations.id, conversations.channel_id, conversations.contact_id, '
                . 'conversations.assigned_user_id, conversations.status, '
                . 'conversations.last_message_at, conversations.unread_count, '
                . 'channels.platform AS channel_platform, '
                . 'contacts.display_name AS contact_display_name, '
                . 'contacts.external_contact_id AS contact_external_id'
            )
            ->join('channels', 'channels.id = conversations.channel_id')
            ->join('contacts', 'contacts.id = conversations.contact_id')
            ->where('channels.business_id', $businessId)
            ->orderBy('conversations.last_message_at', 'DESC')
            ->findAll();
    }

    /**
     * Fetch a conversation row only if it belongs to the given business.
     * Throws ConversationNotFoundException otherwise (mapped to 404 upstream).
     *
     * @return array<string, mixed>
     */
    public function findOwnedConversation(int $conversationId, int $businessId): array
    {
        $row = $this->conversations
            ->select('conversations.*')
            ->join('channels', 'channels.id = conversations.channel_id')
            ->where('conversations.id', $conversationId)
            ->where('channels.business_id', $businessId)
            ->first();

        if ($row === null) {
            throw new ConversationNotFoundException('Conversation not found.');
        }

        return $row;
    }

    /**
     * List messages for a conversation the business owns, chronological order.
     * Uses idx_msg_conversation_created.
     *
     * @return list<array<string, mixed>>
     */
    public function listMessages(int $conversationId, int $businessId): array
    {
        // Ownership check first; throws 404 if not owned.
        $this->findOwnedConversation($conversationId, $businessId);

        return $this->messages
            ->where('conversation_id', $conversationId)
            ->orderBy('created_at', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /**
     * Insert an outbound reply into a conversation the business owns, deliver it
     * through the matching platform adapter when one exists, then bump the parent
     * conversation's last_message_at.
     *
     * Dispatch is delegated to ChannelResolver (platform string -> adapter). When
     * an adapter is registered for the conversation's channel platform (currently
     * only `telegram`), the adapter both delivers to the real platform AND
     * persists the message — its returned status ('sent' or 'failed') reflects
     * the delivery outcome. Platforms without an adapter fall back to persist-only
     * with status='sent' (unchanged pre-integration behaviour).
     *
     * ConversationService stays the orchestrator: ownership check, dispatch,
     * sender attribution, and last_message_at bookkeeping live here; the
     * platform-specific HTTP call lives in the adapter (CLAUDE.md layering).
     *
     * All timestamps are written in UTC (CLAUDE.md).
     *
     * @return array<string, mixed> the created message row
     */
    public function postOutboundMessage(int $conversationId, int $businessId, int $senderUserId, string $body): array
    {
        // Ownership check first; throws 404 if not owned.
        $conversation = $this->findOwnedConversation($conversationId, $businessId);

        $platform = $this->platformFor((int) $conversation['channel_id']);
        $adapter  = $this->channelResolver->resolve($platform);

        if ($adapter !== null) {
            // Adapter owns delivery + persistence. It sets status='sent' on a
            // successful platform send or status='failed' on any platform error
            // (it never throws transport errors up to us). Re-attach the sender
            // so the outbound row is attributed to the agent who replied — the
            // adapter interface intentionally doesn't take a user id.
            $message = $adapter->sendMessage($conversationId, $body);
            $this->messages->update($message['id'], ['sender_user_id' => $senderUserId]);
            $message = $this->messages->find($message['id']);
        } else {
            // No adapter for this platform yet: persist-only, status='sent'.
            $now = gmdate('Y-m-d H:i:s');

            $data = [
                'conversation_id' => $conversationId,
                'direction'       => 'outbound',
                'sender_user_id'  => $senderUserId,
                'body'            => $body,
                'status'          => 'sent',
                'created_at'      => $now,
            ];

            $messageId = $this->messages->insert($data, true);
            $message   = $this->messages->find($messageId);
        }

        // Keep the inbox ordering correct: the reply is now the latest activity.
        // Use the persisted message's own created_at so ordering matches the row.
        $this->conversations->update($conversationId, ['last_message_at' => $message['created_at']]);

        return $message;
    }

    /**
     * Look up the platform string for a channel id.
     */
    private function platformFor(int $channelId): string
    {
        $channel = $this->channels->find($channelId);

        return $channel !== null ? (string) $channel['platform'] : '';
    }
}
