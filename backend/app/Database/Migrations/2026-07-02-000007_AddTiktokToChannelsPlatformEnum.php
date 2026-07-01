<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds 'tiktok' to the channels.platform ENUM.
 *
 * The original ENUM (see 2026-07-02-000003_CreateChannels) allowed
 * whatsapp/messenger/instagram/telegram. Phase 5 introduces a TikTok channel
 * adapter, so the column must accept 'tiktok' as a fifth value.
 *
 * We use CI4's Forge modifyColumn(), which on MySQL emits a proper
 * `ALTER TABLE channels MODIFY COLUMN platform ENUM(...)`. The Forge API
 * expresses ENUM constraints via the `constraint` array, so no raw SQL is
 * needed here. down() restores the original 4-value ENUM.
 *
 * NOTE: down() will fail if any row already holds platform='tiktok', because
 * MySQL cannot narrow an ENUM that still has rows using the dropped value.
 * That is the correct/safe behaviour for a rollback — resolve/migrate those
 * rows first.
 */
class AddTiktokToChannelsPlatformEnum extends Migration
{
    public function up(): void
    {
        $this->forge->modifyColumn('channels', [
            'platform' => [
                'name'       => 'platform',
                'type'       => 'ENUM',
                'constraint' => ['whatsapp', 'messenger', 'instagram', 'telegram', 'tiktok'],
                'null'       => false,
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->modifyColumn('channels', [
            'platform' => [
                'name'       => 'platform',
                'type'       => 'ENUM',
                'constraint' => ['whatsapp', 'messenger', 'instagram', 'telegram'],
                'null'       => false,
            ],
        ]);
    }
}
