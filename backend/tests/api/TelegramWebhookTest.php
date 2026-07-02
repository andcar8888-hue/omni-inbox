<?php

namespace Tests\Api;

use Tests\Support\ApiTestCase;

/**
 * Feature tests for POST /webhooks/telegram — the inbound Telegram Bot API
 * webhook. Runs against the omni_inbox_test DB via ApiTestCase (which truncates
 * all tables before each test).
 *
 * Covers secret-token verification (missing / wrong -> 401), end-to-end
 * persistence (contact + conversation + message), idempotency against a repeated
 * update_id, and the unread_count increment on a second inbound message.
 *
 * The webhook controller reads env('TELEGRAM_WEBHOOK_SECRET'). The committed
 * .env value is the "not configured" sentinel, which fails closed — so these
 * tests override $_ENV with a real secret in setUp() and restore it in tearDown()
 * so the webhook actually processes valid requests.
 *
 * @internal
 */
final class TelegramWebhookTest extends ApiTestCase
{
    private const TEST_SECRET = 'test-webhook-secret-abc123';

    /** @var string|false */
    private $originalSecret;

    protected function setUp(): void
    {
        parent::setUp();

        // Preserve and override the webhook secret so the controller's
        // constant-time check passes for valid requests.
        $this->originalSecret = $_ENV['TELEGRAM_WEBHOOK_SECRET'] ?? false;
        $_ENV['TELEGRAM_WEBHOOK_SECRET'] = self::TEST_SECRET;
    }

    protected function tearDown(): void
    {
        if ($this->originalSecret === false) {
            unset($_ENV['TELEGRAM_WEBHOOK_SECRET']);
        } else {
            $_ENV['TELEGRAM_WEBHOOK_SECRET'] = $this->originalSecret;
        }

        parent::tearDown();
    }

    /**
     * Seed a business + telegram channel so inbound updates can be attributed.
     * Returns the channel id.
     */
    private function seedTelegramChannel(): int
    {
        $businessId = $this->makeBusiness('TG Biz');

        return $this->makeChannel($businessId, 'telegram', 'omni_test_bot');
    }

    /**
     * A realistic private-chat text-message Telegram Update.
     *
     * @return array<string, mixed>
     */
    private function textUpdate(int $updateId = 500001, string $text = 'Hello there', int $chatId = 111222333): array
    {
        return [
            'update_id' => $updateId,
            'message'   => [
                'message_id' => 10,
                'from'       => [
                    'id'         => $chatId,
                    'is_bot'     => false,
                    'first_name' => 'Jane',
                    'last_name'  => 'Doe',
                    'username'   => 'janedoe',
                ],
                'chat' => [
                    'id'         => $chatId,
                    'first_name' => 'Jane',
                    'last_name'  => 'Doe',
                    'username'   => 'janedoe',
                    'type'       => 'private',
                ],
                'date' => 1751443200,
                'text' => $text,
            ],
        ];
    }

    /**
     * POST a decoded Telegram Update to the webhook with an explicit secret-token
     * header value.
     */
    private function postUpdate(array $update, ?string $secretHeader = self::TEST_SECRET)
    {
        $headers = [];
        if ($secretHeader !== null) {
            $headers['X-Telegram-Bot-Api-Secret-Token'] = $secretHeader;
        }

        return $this->withHeaders($headers)
            ->withBody(json_encode($update))
            ->post('/webhooks/telegram');
    }

    public function testMissingSecretHeaderReturns401(): void
    {
        $this->seedTelegramChannel();

        $result = $this->postUpdate($this->textUpdate(), null);

        $result->assertStatus(401);
        $result->assertJSONFragment(['error' => ['code' => 'invalid_secret']]);
        $this->dontSeeInDatabase('messages', ['direction' => 'inbound']);
    }

    public function testWrongSecretHeaderReturns401(): void
    {
        $this->seedTelegramChannel();

        $result = $this->postUpdate($this->textUpdate(), 'totally-wrong-secret');

        $result->assertStatus(401);
        $result->assertJSONFragment(['error' => ['code' => 'invalid_secret']]);
        $this->dontSeeInDatabase('messages', ['direction' => 'inbound']);
    }

    public function testValidPayloadCreatesContactConversationAndMessage(): void
    {
        $channelId = $this->seedTelegramChannel();

        $result = $this->postUpdate($this->textUpdate(500001, 'First message'));

        $result->assertStatus(200);
        $result->assertJSONFragment(['data' => ['result' => 'stored']]);

        // Contact created for this chat on this channel.
        $this->seeInDatabase('contacts', [
            'channel_id'          => $channelId,
            'external_contact_id' => '111222333',
            'display_name'        => 'Jane Doe',
        ]);

        $contact = db_connect()->table('contacts')
            ->where('channel_id', $channelId)
            ->where('external_contact_id', '111222333')
            ->get()->getRowArray();

        // Conversation created for (channel, contact), unread_count bumped to 1.
        $this->seeInDatabase('conversations', [
            'channel_id'   => $channelId,
            'contact_id'   => $contact['id'],
            'status'       => 'open',
            'unread_count' => 1,
        ]);

        // Inbound message stored with the channel-scoped external id.
        $this->seeInDatabase('messages', [
            'direction'           => 'inbound',
            'external_message_id' => 'tg:' . $channelId . ':500001',
            'body'                => 'First message',
            'status'              => 'delivered',
        ]);
    }

    public function testDuplicateUpdateIdIsIdempotent(): void
    {
        $channelId = $this->seedTelegramChannel();
        $update    = $this->textUpdate(500002, 'Only once');

        $first = $this->postUpdate($update);
        $first->assertStatus(200);
        $first->assertJSONFragment(['data' => ['result' => 'stored']]);

        // Exact same update_id delivered again (Telegram retry).
        $second = $this->postUpdate($update);
        $second->assertStatus(200);
        $second->assertJSONFragment(['data' => ['result' => 'duplicate']]);

        // Only one message row exists for this dedupe key.
        $count = db_connect()->table('messages')
            ->where('external_message_id', 'tg:' . $channelId . ':500002')
            ->countAllResults();
        $this->assertSame(1, $count);
    }

    public function testSecondInboundMessageIncrementsUnreadCount(): void
    {
        $channelId = $this->seedTelegramChannel();

        // First inbound message: unread_count -> 1.
        $this->postUpdate($this->textUpdate(500003, 'first', 444555666))
            ->assertStatus(200);

        // Second, different update on the SAME chat -> reuses the conversation.
        $this->postUpdate($this->textUpdate(500004, 'second', 444555666))
            ->assertStatus(200);

        $contact = db_connect()->table('contacts')
            ->where('channel_id', $channelId)
            ->where('external_contact_id', '444555666')
            ->get()->getRowArray();

        // Exactly one conversation (not two) and unread_count is now 2.
        $conversations = db_connect()->table('conversations')
            ->where('channel_id', $channelId)
            ->where('contact_id', $contact['id'])
            ->get()->getResultArray();

        $this->assertCount(1, $conversations);
        $this->assertSame('2', (string) $conversations[0]['unread_count']);
    }

    public function testNonTextUpdateIsIgnoredAndStoresNothing(): void
    {
        $this->seedTelegramChannel();

        $editedMessageUpdate = [
            'update_id'      => 500005,
            'edited_message' => [
                'message_id' => 11,
                'chat'       => ['id' => 111222333, 'type' => 'private'],
                'date'       => 1751443200,
                'edit_date'  => 1751443260,
                'text'       => 'edited',
            ],
        ];

        $result = $this->postUpdate($editedMessageUpdate);

        $result->assertStatus(200);
        $result->assertJSONFragment(['data' => ['result' => 'ignored']]);
        $this->dontSeeInDatabase('messages', ['direction' => 'inbound']);
    }
}
