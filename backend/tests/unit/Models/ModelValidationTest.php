<?php

namespace Tests\Unit\Models;

use App\Models\ConversationModel;
use App\Models\MessageModel;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Pure (non-DB) validation-rule tests.
 *
 * We exercise the model's Validation service directly via validate() on the
 * rule set, avoiding any rule that hits the database (is_unique). Those
 * DB-backed constraints are covered by the schema's UNIQUE keys and belong in
 * an integration test with a live DB (see notes for qa-engineer).
 *
 * @internal
 */
final class ModelValidationTest extends CIUnitTestCase
{
    /**
     * Validate a single field's rules in isolation using the validation service,
     * so we never trigger DB-backed rules on unrelated fields.
     */
    private function validateField(object $model, string $field, mixed $value): bool
    {
        $rules = $model->getValidationRules();
        $this->assertArrayHasKey($field, $rules, "Field {$field} has no rules");

        $validation = service('validation');
        $validation->reset();
        $validation->setRule($field, $field, $rules[$field]);

        return $validation->run([$field => $value]);
    }

    public function testUserRoleRejectsUnknownEnum(): void
    {
        $m = new UserModel();
        $this->assertFalse($this->validateField($m, 'role', 'superadmin'));
        $this->assertTrue($this->validateField($m, 'role', 'owner'));
        $this->assertTrue($this->validateField($m, 'role', 'agent'));
    }

    public function testUserNameRequired(): void
    {
        $m = new UserModel();
        $this->assertFalse($this->validateField($m, 'name', ''));
        $this->assertTrue($this->validateField($m, 'name', 'Ada Lovelace'));
    }

    public function testConversationStatusEnum(): void
    {
        $m = new ConversationModel();
        $this->assertFalse($this->validateField($m, 'status', 'archived'));
        $this->assertTrue($this->validateField($m, 'status', 'open'));
        $this->assertTrue($this->validateField($m, 'status', 'pending'));
        $this->assertTrue($this->validateField($m, 'status', 'closed'));
    }

    public function testConversationLastMessageAtRequired(): void
    {
        $m = new ConversationModel();
        $this->assertFalse($this->validateField($m, 'last_message_at', ''));
        $this->assertTrue($this->validateField($m, 'last_message_at', '2026-07-02 10:00:00'));
    }

    public function testMessageDirectionEnum(): void
    {
        $m = new MessageModel();
        $this->assertFalse($this->validateField($m, 'direction', 'internal'));
        $this->assertTrue($this->validateField($m, 'direction', 'inbound'));
        $this->assertTrue($this->validateField($m, 'direction', 'outbound'));
    }

    public function testMessageStatusEnum(): void
    {
        $m = new MessageModel();
        $this->assertFalse($this->validateField($m, 'status', 'queued'));
        $this->assertTrue($this->validateField($m, 'status', 'sent'));
        $this->assertTrue($this->validateField($m, 'status', 'delivered'));
        $this->assertTrue($this->validateField($m, 'status', 'read'));
        $this->assertTrue($this->validateField($m, 'status', 'failed'));
    }
}
