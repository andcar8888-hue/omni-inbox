<?php

namespace Tests\Api;

use Tests\Support\ApiTestCase;

/**
 * @internal
 */
final class ConversationsTest extends ApiTestCase
{
    public function testListConversationsRequiresAuth(): void
    {
        $result = $this->get('/api/v1/conversations');

        $result->assertStatus(401);
        $result->assertJSONFragment(['error' => ['code' => 'missing_token']]);
    }

    public function testListConversationsRejectsInvalidToken(): void
    {
        $result = $this->withHeaders($this->authHeaders('not-a-real-token'))
            ->get('/api/v1/conversations');

        $result->assertStatus(401);
        $result->assertJSONFragment(['error' => ['code' => 'invalid_token']]);
    }

    public function testListConversationsReturnsOnlyOwnBusinessOrderedByLastMessage(): void
    {
        // Business A with two conversations at different times.
        $businessId = $this->makeBusiness('A');
        $userId     = $this->makeUser($businessId, 'a@test.com', 'pw');
        $channelId  = $this->makeChannel($businessId);
        $c1         = $this->makeContact($channelId, 'c1');
        $c2         = $this->makeContact($channelId, 'c2');
        $olderConv  = $this->makeConversation($channelId, $c1, '2026-01-01 10:00:00');
        $newerConv  = $this->makeConversation($channelId, $c2, '2026-06-01 10:00:00');

        // Business B with its own conversation (must NOT appear).
        $otherBiz     = $this->makeBusiness('B');
        $otherChannel = $this->makeChannel($otherBiz);
        $otherContact = $this->makeContact($otherChannel, 'other');
        $this->makeConversation($otherChannel, $otherContact, '2026-07-01 10:00:00');

        $token  = $this->tokenFor($userId, $businessId);
        $result = $this->withHeaders($this->authHeaders($token))->get('/api/v1/conversations');

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);
        $rows = $body['data'];

        $this->assertCount(2, $rows, 'Only business A conversations should be returned');
        // Ordered by last_message_at DESC: newer first.
        $this->assertSame($newerConv, (int) $rows[0]['id']);
        $this->assertSame($olderConv, (int) $rows[1]['id']);
    }

    public function testListConversationsEmptyState(): void
    {
        $businessId = $this->makeBusiness('Empty');
        $userId     = $this->makeUser($businessId, 'empty@test.com', 'pw');

        $token  = $this->tokenFor($userId, $businessId);
        $result = $this->withHeaders($this->authHeaders($token))->get('/api/v1/conversations');

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);
        $this->assertSame([], $body['data']);
    }
}
