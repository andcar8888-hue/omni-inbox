<?php

namespace App\Controllers\Api\V1;

use App\Services\AuthService;
use App\Services\ConversationService;
use App\Services\Exceptions\ConversationNotFoundException;

class ConversationsController extends BaseApiController
{
    private ConversationService $conversations;

    public function __construct()
    {
        $this->conversations = new ConversationService();
    }

    /**
     * GET /api/v1/conversations
     * Lists conversations for the authenticated user's business.
     */
    public function index()
    {
        $user = AuthService::currentUser();
        if ($user === null) {
            return $this->respondError(401, 'unauthenticated', 'Authentication required.');
        }

        $rows = $this->conversations->listForBusiness($user['business_id']);

        return $this->respondSuccess($rows);
    }

    /**
     * GET /api/v1/conversations/{id}/messages
     * Lists messages for a conversation the caller's business owns.
     */
    public function messages(int $id)
    {
        $user = AuthService::currentUser();
        if ($user === null) {
            return $this->respondError(401, 'unauthenticated', 'Authentication required.');
        }

        try {
            $rows = $this->conversations->listMessages($id, $user['business_id']);
        } catch (ConversationNotFoundException) {
            return $this->respondError(404, 'conversation_not_found', 'Conversation not found.');
        }

        return $this->respondSuccess($rows);
    }

    /**
     * POST /api/v1/conversations/{id}/messages
     * Sends an outbound reply. No platform integration yet.
     * Body: { "body": "..." }
     */
    public function sendMessage(int $id)
    {
        $user = AuthService::currentUser();
        if ($user === null) {
            return $this->respondError(401, 'unauthenticated', 'Authentication required.');
        }

        $input = $this->jsonInput();

        $rules = [
            'body' => 'required|string|max_length[65535]',
        ];

        if (! $this->validateData($input, $rules)) {
            return $this->respondError(
                422,
                'validation_error',
                'The submitted data is invalid.',
                $this->validator->getErrors()
            );
        }

        try {
            $message = $this->conversations->postOutboundMessage(
                $id,
                $user['business_id'],
                $user['id'],
                (string) $input['body']
            );
        } catch (ConversationNotFoundException) {
            return $this->respondError(404, 'conversation_not_found', 'Conversation not found.');
        }

        return $this->respondSuccess($message, 201);
    }
}
