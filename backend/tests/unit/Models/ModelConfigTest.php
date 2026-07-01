<?php

namespace Tests\Unit\Models;

use App\Models\BusinessModel;
use App\Models\ChannelModel;
use App\Models\ContactModel;
use App\Models\ConversationModel;
use App\Models\MessageModel;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use ReflectionClass;

/**
 * Configuration-level assertions for the data-layer models.
 *
 * These tests intentionally avoid a live database. They lock down the model
 * contract (table name, allowed fields, timestamp handling, ENUM value lists)
 * so a future refactor cannot silently drift from docs/db-schema.sql.
 *
 * @internal
 */
final class ModelConfigTest extends CIUnitTestCase
{
    /**
     * Reads a protected property off a freshly built model instance.
     */
    private function prop(object $model, string $name): mixed
    {
        $ref = new ReflectionClass($model);
        $p   = $ref->getProperty($name);
        $p->setAccessible(true);

        return $p->getValue($model);
    }

    public function testBusinessModelConfig(): void
    {
        $m = new BusinessModel();
        $this->assertSame('businesses', $this->prop($m, 'table'));
        $this->assertSame(['name', 'created_at'], $this->prop($m, 'allowedFields'));
        // Draft schema has created_at only -> automatic timestamps disabled.
        $this->assertFalse($this->prop($m, 'useTimestamps'));
    }

    public function testUserModelConfig(): void
    {
        $m = new UserModel();
        $this->assertSame('users', $this->prop($m, 'table'));
        $this->assertContains('email', $this->prop($m, 'allowedFields'));
        $this->assertContains('password_hash', $this->prop($m, 'allowedFields'));
        $this->assertContains('role', $this->prop($m, 'allowedFields'));
        $this->assertFalse($this->prop($m, 'useTimestamps'));
    }

    public function testChannelModelConfig(): void
    {
        $m = new ChannelModel();
        $this->assertSame('channels', $this->prop($m, 'table'));
        // Encrypted credentials must be writable through the model.
        $this->assertContains('credentials_encrypted', $this->prop($m, 'allowedFields'));
        $this->assertFalse($this->prop($m, 'useTimestamps'));
    }

    public function testContactModelConfig(): void
    {
        $m = new ContactModel();
        $this->assertSame('contacts', $this->prop($m, 'table'));
        $this->assertContains('external_contact_id', $this->prop($m, 'allowedFields'));
        $this->assertFalse($this->prop($m, 'useTimestamps'));
    }

    public function testConversationModelConfig(): void
    {
        $m = new ConversationModel();
        $this->assertSame('conversations', $this->prop($m, 'table'));
        // The draft schema defines no created_at on conversations, so it must
        // not appear in allowedFields (we don't invent columns).
        $this->assertNotContains('created_at', $this->prop($m, 'allowedFields'));
        $this->assertNotContains('updated_at', $this->prop($m, 'allowedFields'));
        $this->assertContains('last_message_at', $this->prop($m, 'allowedFields'));
        $this->assertFalse($this->prop($m, 'useTimestamps'));
    }

    public function testMessageModelConfig(): void
    {
        $m = new MessageModel();
        $this->assertSame('messages', $this->prop($m, 'table'));
        $this->assertContains('external_message_id', $this->prop($m, 'allowedFields'));
        $this->assertContains('attachments_json', $this->prop($m, 'allowedFields'));
        $this->assertFalse($this->prop($m, 'useTimestamps'));
    }
}
