<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds exactly one Telegram channel row for the dev business so an inbound
 * Telegram webhook can be attributed to a business locally, and so an outbound
 * reply on a telegram conversation has a channel to resolve.
 *
 * Depends on DevLoginSeeder having run first (it creates the dev business +
 * owner). Run both in order:
 *
 *   php spark db:seed DevLoginSeeder
 *   php spark db:seed DevTelegramChannelSeeder
 *
 * What it creates:
 *   - business_id           = the dev business owned by DevLoginSeeder::TEST_EMAIL
 *                             (resolved via that user's business_id, so it stays
 *                             correct even if the business is not literally id 1).
 *   - platform              = 'telegram'
 *   - external_account_id   = 'your_bot_username'  (PLACEHOLDER — replace with
 *                             your real @BotFather bot username).
 *   - credentials_encrypted = a placeholder string. NOTE: Telegram does NOT read
 *                             the token from this column — TelegramChannel pulls
 *                             the real token from env('TELEGRAM_BOT_TOKEN'). This
 *                             value exists only to satisfy the NOT NULL column.
 *
 * Idempotent: keyed on the composite UNIQUE (platform, external_account_id), so
 * re-running does not create a duplicate. Also no-ops (with a note) if the dev
 * business is missing, rather than inserting an orphaned channel.
 *
 * Do NOT run against a production database.
 */
class DevTelegramChannelSeeder extends Seeder
{
    public const PLATFORM            = 'telegram';
    public const BOT_USERNAME        = 'your_bot_username'; // PLACEHOLDER — replace.
    public const CREDENTIALS_PLACEHOLDER = 'unused-telegram-token-comes-from-env';

    public function run()
    {
        // Resolve the dev business via the DevLoginSeeder owner, so we attribute
        // the channel to the right tenant even if it is not row id 1.
        $owner = $this->db->table('users')
            ->where('email', DevLoginSeeder::TEST_EMAIL)
            ->get()
            ->getRowArray();

        if ($owner === null) {
            // No dev business yet — run DevLoginSeeder first. Don't insert an
            // orphaned channel.
            log_message('warning', 'DevTelegramChannelSeeder: dev owner (' . DevLoginSeeder::TEST_EMAIL . ') not found; run DevLoginSeeder first. Skipping.');

            return;
        }

        $businessId = (int) $owner['business_id'];

        // Idempotency: the DB enforces UNIQUE (platform, external_account_id), so
        // skip if a matching row already exists (safe to re-run).
        $existing = $this->db->table('channels')
            ->where('platform', self::PLATFORM)
            ->where('external_account_id', self::BOT_USERNAME)
            ->get()
            ->getRowArray();

        if ($existing !== null) {
            return;
        }

        $this->db->table('channels')->insert([
            'business_id'           => $businessId,
            'platform'              => self::PLATFORM,
            'external_account_id'   => self::BOT_USERNAME,
            'credentials_encrypted' => self::CREDENTIALS_PLACEHOLDER,
            'created_at'            => gmdate('Y-m-d H:i:s'),
        ]);
    }
}
