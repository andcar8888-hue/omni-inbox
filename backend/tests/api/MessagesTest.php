<?php

namespace Tests\Api;

use Tests\Support\ApiTestCase;

/**
 * @internal
 */
final class MessagesTest extends ApiTestCase
{
    public function testGetMessagesForOwnConversationReturnsThemChronologically(): void
    {
        $ctx   = $this->makeFullBusiness('owner@a.test', 'pw', '2026-06-01 10:00:00');
        $db    = db_connect();
        $convId = $ctx['conversation_id'];

        $db->table('messages')->insert([
            'conversation_id' => $convId,
            'direction'       => 'inbound',
            'body'            => 'first',
            'status'          => 'delivered',
            'created_at'      => '2026-06-01 09:00:00',
        ]);
        $db->table('messages')->insert([
            'conversation_id' => $convId,
            'direction'       => 'outbound',
            'sender_user_id'  => $ctx['user_id'],
            'body'            => 'second',
            'status'          => 'sent',
            'created_at'      => '2026-06-01 09:05:00',
        ]);

        $token  = $this->tokenFor($ctx['user_id'], $ctx['business_id']);
        $result = $this->withHeaders($this->authHeaders($token))
            ->get('/api/v1/conversations/' . $convId . '/messages');

        $result->assertStatus(200);
        $rows = json_decode($result->getJSON(), true)['data'];
        $this->assertCount(2, $rows);
        $this->assertSame('first', $rows[0]['body']);
        $this->assertSame('second', $rows[1]['body']);
    }

    public function testGetMessagesForOtherBusinessConversationReturns404NotLeaking(): void
    {
        // Caller belongs to business A.
        $mine = $this->makeFullBusiness('mine@a.test', 'pw', '2026-06-01 10:00:00');

        // A conversation that belongs to business B.
        $theirs = $this->makeFullBusiness('theirs@b.test', 'pw', '2026-06-01 10:00:00');

        $token  = $this->tokenFor($mine['user_id'], $mine['business_id']);
        $result = $this->withHeaders($this->authHeaders($token))
            ->get('/api/v1/conversations/' . $theirs['conversation_id'] . '/messages');

        $result->assertStatus(404);
        $result->assertJSONFragment(['error' => ['code' => 'conversation_not_found']]);
    }

    public function testGetMessagesForNonexistentConversationReturns404(): void
    {
        $ctx    = $this->makeFullBusiness('x@a.test', 'pw', '2026-06-01 10:00:00');
        $token  = $this->tokenFor($ctx['user_id'], $ctx['business_id']);

        $result = $this->withHeaders($this->authHeaders($token))
            ->get('/api/v1/conversations/999999/messages');

        $result->assertStatus(404);
    }

    public function testPostMessagePersistsOutboundAndUpdatesLastMessageAt(): void
    {
        $ctx    = $this->makeFullBusiness('poster@a.test', 'pw', '2026-01-01 00:00:00');
        $convId = $ctx['conversation_id'];
        $token  = $this->tokenFor($ctx['user_id'], $ctx['business_id']);

        $before = db_connect()->table('conversations')->where('id', $convId)->get()->getRowArray();
        $this->assertSame('2026-01-01 00:00:00', $before['last_message_at']);

        $result = $this->withHeaders($this->authHeaders($token))
            ->withBodyFormat('json')
            ->post('/api/v1/conversations/' . $convId . '/messages', [
                'body' => 'Hello from the agent',
            ]);

        $result->assertStatus(201);
        $created = json_decode($result->getJSON(), true)['data'];

        $this->assertSame('outbound', $created['direction']);
        $this->assertSame((string) $ctx['user_id'], (string) $created['sender_user_id']);
        $this->assertSame('sent', $created['status']);
        $this->assertSame('Hello from the agent', $created['body']);

        // Persisted in DB.
        $this->seeInDatabase('messages', [
            'id'              => $created['id'],
            'conversation_id' => $convId,
            'direction'       => 'outbound',
            'sender_user_id'  => $ctx['user_id'],
            'status'          => 'sent',
            'body'            => 'Hello from the agent',
        ]);

        // last_message_at bumped past the original value.
        $after = db_connect()->table('conversations')->where('id', $convId)->get()->getRowArray();
        $this->assertNotSame('2026-01-01 00:00:00', $after['last_message_at']);
        $this->assertSame($created['created_at'], $after['last_message_at']);
    }

    public function testPostMessageValidationRequiresBody(): void
    {
        $ctx    = $this->makeFullBusiness('v@a.test', 'pw', '2026-01-01 00:00:00');
        $token  = $this->tokenFor($ctx['user_id'], $ctx['business_id']);

        $result = $this->withHeaders($this->authHeaders($token))
            ->withBodyFormat('json')
            ->post('/api/v1/conversations/' . $ctx['conversation_id'] . '/messages', []);

        $result->assertStatus(422);
        $result->assertJSONFragment(['error' => ['code' => 'validation_error']]);
    }

    public function testPostMessageToOtherBusinessConversationReturns404(): void
    {
        $mine   = $this->makeFullBusiness('m@a.test', 'pw', '2026-01-01 00:00:00');
        $theirs = $this->makeFullBusiness('t@b.test', 'pw', '2026-01-01 00:00:00');
        $token  = $this->tokenFor($mine['user_id'], $mine['business_id']);

        $result = $this->withHeaders($this->authHeaders($token))
            ->withBodyFormat('json')
            ->post('/api/v1/conversations/' . $theirs['conversation_id'] . '/messages', [
                'body' => 'should not be allowed',
            ]);

        $result->assertStatus(404);
        // And nothing was written to the other business's conversation.
        $this->dontSeeInDatabase('messages', [
            'conversation_id' => $theirs['conversation_id'],
            'body'            => 'should not be allowed',
        ]);
    }
}
