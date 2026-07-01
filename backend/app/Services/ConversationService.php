<?php

namespace App\Services;

use App\Models\ConversationModel;
use App\Models\MessageModel;
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

    public function __construct(?ConversationModel $conversations = null, ?MessageModel $messages = null)
    {
        $this->conversations = $conversations ?? new ConversationModel();
        $this->messages      = $messages ?? new MessageModel();
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
     * Insert an outbound reply into a conversation the business owns, then bump
     * the parent conversation's last_message_at. No platform integration yet.
     *
     * All timestamps are written in UTC (CLAUDE.md).
     *
     * @return array<string, mixed> the created message row
     */
    public function postOutboundMessage(int $conversationId, int $businessId, int $senderUserId, string $body): array
    {
        // Ownership check first; throws 404 if not owned.
        $this->findOwnedConversation($conversationId, $businessId);

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

        // Keep the inbox ordering correct: the reply is now the latest activity.
        $this->conversations->update($conversationId, ['last_message_at' => $now]);

        return $this->messages->find($messageId);
    }
}
