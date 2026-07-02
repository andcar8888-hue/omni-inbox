<?php

namespace Tests\Unit\Services\Channels;

use App\Services\Channels\TelegramChannel;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Unit tests for TelegramChannel::normalizeIncoming() — the inbound Telegram
 * `Update` -> common Message DTO mapping. No DB, no HTTP; pure parsing.
 *
 * Fixtures use the real Telegram Bot API `Update`/`Message`/`Chat`/`User` field
 * names (update_id, message.message_id, message.from.{id,is_bot,first_name,...},
 * message.chat.{id,type,...}, message.date, message.text).
 *
 * @internal
 */
final class TelegramChannelTest extends CIUnitTestCase
{
    /**
     * A realistic private-chat text-message Telegram Update.
     *
     * @return array<string, mixed>
     */
    private function textUpdate(): array
    {
        return [
            'update_id' => 987654321,
            'message'   => [
                'message_id' => 42,
                'from'       => [
                    'id'         => 111222333,
                    'is_bot'     => false,
                    'first_name' => 'Jane',
                    'last_name'  => 'Doe',
                    'username'   => 'janedoe',
                ],
                'chat' => [
                    'id'         => 111222333,
                    'first_name' => 'Jane',
                    'last_name'  => 'Doe',
                    'username'   => 'janedoe',
                    'type'       => 'private',
                ],
                'date' => 1751443200, // 2025-07-02 08:00:00 UTC
                'text' => 'Hi, is this still available?',
            ],
        ];
    }

    public function testNormalizeIncomingParsesTextMessageIntoDto(): void
    {
        $dto = (new TelegramChannel())->normalizeIncoming($this->textUpdate());

        $this->assertIsArray($dto);
        $this->assertSame('111222333', $dto['external_contact_id']);
        $this->assertSame('987654321', $dto['external_message_id']);
        $this->assertSame('Jane Doe', $dto['contact_display_name']);
        $this->assertSame('Hi, is this still available?', $dto['body']);
        $this->assertSame('2025-07-02 08:00:00', $dto['sent_at']);
    }

    public function testNormalizeIncomingFallsBackToUsernameWhenNoName(): void
    {
        $update = $this->textUpdate();
        unset($update['message']['from']['first_name'], $update['message']['from']['last_name']);
        unset($update['message']['chat']['first_name'], $update['message']['chat']['last_name']);

        $dto = (new TelegramChannel())->normalizeIncoming($update);

        $this->assertIsArray($dto);
        $this->assertSame('@janedoe', $dto['contact_display_name']);
    }

    public function testNormalizeIncomingReturnsNullForEditedMessage(): void
    {
        // An edited_message update carries no top-level `message` key.
        $update = [
            'update_id'      => 987654322,
            'edited_message' => [
                'message_id' => 42,
                'chat'       => ['id' => 111222333, 'type' => 'private'],
                'date'       => 1751443260,
                'edit_date'  => 1751443300,
                'text'       => 'edited text',
            ],
        ];

        $this->assertNull((new TelegramChannel())->normalizeIncoming($update));
    }

    public function testNormalizeIncomingReturnsNullForPhotoWithoutText(): void
    {
        // A photo message: `message` present but no `text`.
        $update = [
            'update_id' => 987654323,
            'message'   => [
                'message_id' => 43,
                'from'       => ['id' => 111222333, 'is_bot' => false, 'first_name' => 'Jane'],
                'chat'       => ['id' => 111222333, 'type' => 'private'],
                'date'       => 1751443200,
                'photo'      => [
                    ['file_id' => 'AgACAgEx', 'file_unique_id' => 'AQADx', 'width' => 90, 'height' => 90],
                ],
            ],
        ];

        $this->assertNull((new TelegramChannel())->normalizeIncoming($update));
    }

    public function testNormalizeIncomingReturnsNullWhenUpdateIdMissing(): void
    {
        $update = $this->textUpdate();
        unset($update['update_id']);

        $this->assertNull((new TelegramChannel())->normalizeIncoming($update));
    }
}
