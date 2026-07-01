<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds exactly one test business and one owner user so there is something to
 * log in with locally in the DEV environment.
 *
 * Run with:  php spark db:seed DevLoginSeeder
 *
 * Test credentials (documented in the root README):
 *   email:    owner@test.com
 *   password: OmniDev!2026
 *
 * The password is hashed with password_hash(PASSWORD_DEFAULT) (bcrypt), which
 * the /api/v1/auth/login endpoint verifies via password_verify().
 *
 * Idempotent: re-running does not create duplicates (users.email is UNIQUE).
 */
class DevLoginSeeder extends Seeder
{
    public const TEST_EMAIL    = 'owner@test.com';
    public const TEST_PASSWORD = 'OmniDev!2026';

    public function run()
    {
        $now = gmdate('Y-m-d H:i:s');

        // Skip if the test user already exists (keeps the seeder safe to re-run).
        $existing = $this->db->table('users')
            ->where('email', self::TEST_EMAIL)
            ->get()
            ->getRowArray();

        if ($existing !== null) {
            return;
        }

        $this->db->table('businesses')->insert([
            'name'       => 'Acme Test Co',
            'created_at' => $now,
        ]);
        $businessId = $this->db->insertID();

        $this->db->table('users')->insert([
            'business_id'   => $businessId,
            'name'          => 'Test Owner',
            'email'         => self::TEST_EMAIL,
            'password_hash' => password_hash(self::TEST_PASSWORD, PASSWORD_DEFAULT),
            'role'          => 'owner',
            'created_at'    => $now,
        ]);
    }
}
