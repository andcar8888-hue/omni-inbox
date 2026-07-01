<?php

namespace Tests\Unit\Services;

use App\Services\ConversationService;
use App\Services\Exceptions\ConversationNotFoundException;
use Tests\Support\ApiTestCase;

/**
 * DB-backed unit tests for ConversationService tenant isolation and writes.
 *
 * @internal
 */
final class ConversationServiceTest extends ApiTestCase
{
    public function testListForBusinessReturnsOnlyOwnedOrderedDesc(): void
    {
        $biz     = $this->makeBusiness('A');
        $channel = $this->makeChannel($biz);
        $c1      = $this->makeContact($channel, 'c1');
        $c2      = $this->makeContact($channel, 'c2');
        $older   = $this->makeConversation($channel, $c1, '2026-01-01 00:00:00');
        $newer   = $this->makeConversation($channel, $c2, '2026-05-01 00:00:00');

        // Foreign business conversation.
        $otherBiz     = $this->makeBusiness('B');
        $otherChannel = $this->makeChannel($otherBiz);
        $otherContact = $this->makeContact($otherChannel, 'o1');
        $this->makeConversation($otherChannel, $otherContact, '2026-09-01 00:00:00');

        $rows = (new ConversationService())->listForBusiness($biz);

        $this->assertCount(2, $rows);
        $this->assertSame($newer, (int) $rows[0]['id']);
        $this->assertSame($older, (int) $rows[1]['id']);
    }

    public function testFindOwnedConversationThrowsForOtherBusiness(): void
    {
        $mineBiz     = $this->makeBusiness('A');
        $otherBiz    = $this->makeBusiness('B');
        $otherChan   = $this->makeChannel($otherBiz);
        $otherCont   = $this->makeContact($otherChan);
        $otherConvId = $this->makeConversation($otherChan, $otherCont, '2026-01-01 00:00:00');

        $this->expectException(ConversationNotFoundException::class);
        (new ConversationService())->findOwnedConversation($otherConvId, $mineBiz);
    }

    public function testPostOutboundMessagePersistsAndBumpsLastMessageAt(): void
    {
        $biz     = $this->makeBusiness('A');
        $user    = $this->makeUser($biz, 'a@test.com', 'pw');
        $channel = $this->makeChannel($biz);
        $contact = $this->makeContact($channel);
        $conv    = $this->makeConversation($channel, $contact, '2020-01-01 00:00:00');

        $msg = (new ConversationService())->postOutboundMessage($conv, $biz, $user, 'Hi there');

        $this->assertSame('outbound', $msg['direction']);
        $this->assertSame('sent', $msg['status']);
        $this->assertSame((string) $user, (string) $msg['sender_user_id']);
        $this->assertSame('Hi there', $msg['body']);

        $after = db_connect()->table('conversations')->where('id', $conv)->get()->getRowArray();
        $this->assertSame($msg['created_at'], $after['last_message_at']);
    }

    public function testPostOutboundMessageRejectsForeignConversation(): void
    {
        $mineBiz  = $this->makeBusiness('A');
        $mineUser = $this->makeUser($mineBiz, 'a@test.com', 'pw');

        $otherBiz  = $this->makeBusiness('B');
        $otherChan = $this->makeChannel($otherBiz);
        $otherCont = $this->makeContact($otherChan);
        $otherConv = $this->makeConversation($otherChan, $otherCont, '2026-01-01 00:00:00');

        $this->expectException(ConversationNotFoundException::class);
        (new ConversationService())->postOutboundMessage($otherConv, $mineBiz, $mineUser, 'nope');
    }
}
