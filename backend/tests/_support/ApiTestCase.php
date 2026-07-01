<?php

namespace Tests\Support;

use App\Services\JwtService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Base class for the /api/v1 feature tests.
 *
 * Runs against the `tests` DB group (omni_inbox_test). DatabaseTestTrait wraps
 * each test in a transaction and rolls it back, so the DB is clean between tests.
 * Migrations are applied once via `php spark migrate --env testing`.
 */
abstract class ApiTestCase extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    // Do not let the trait run migrations/seeds automatically; we migrate the
    // test DB out of band and rely on transaction rollback for isolation.
    protected $migrate     = false;
    protected $migrateOnce = false;
    protected $refresh     = false;
    protected $namespace   = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset any leftover authenticated identity between requests.
        service('authContext')->clear();

        // DatabaseTestTrait provides migration-based isolation, not per-test
        // transactions. Empty every table before each test so tests are
        // independent regardless of order. Children first (FK order).
        $db = db_connect();
        $db->query('SET FOREIGN_KEY_CHECKS = 0');
        foreach (['messages', 'conversations', 'contacts', 'channels', 'users', 'businesses'] as $table) {
            $db->table($table)->truncate();
        }
        $db->query('SET FOREIGN_KEY_CHECKS = 1');
    }

    /**
     * Insert a business and return its id.
     */
    protected function makeBusiness(string $name = 'Biz'): int
    {
        $db  = db_connect();
        $now = gmdate('Y-m-d H:i:s');
        $db->table('businesses')->insert(['name' => $name, 'created_at' => $now]);

        return (int) $db->insertID();
    }

    /**
     * Insert a user for a business. Returns the user id.
     */
    protected function makeUser(int $businessId, string $email, string $password, string $role = 'owner'): int
    {
        $db  = db_connect();
        $now = gmdate('Y-m-d H:i:s');
        $db->table('users')->insert([
            'business_id'   => $businessId,
            'name'          => 'User ' . $email,
            'email'         => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role'          => $role,
            'created_at'    => $now,
        ]);

        return (int) $db->insertID();
    }

    /**
     * Insert a channel for a business. Returns the channel id.
     */
    protected function makeChannel(int $businessId, string $platform = 'whatsapp', ?string $externalAccountId = null): int
    {
        $db  = db_connect();
        $now = gmdate('Y-m-d H:i:s');
        $db->table('channels')->insert([
            'business_id'           => $businessId,
            'platform'              => $platform,
            'external_account_id'   => $externalAccountId ?? ('acct_' . uniqid()),
            'credentials_encrypted' => 'encrypted-placeholder',
            'created_at'            => $now,
        ]);

        return (int) $db->insertID();
    }

    /**
     * Insert a contact for a channel. Returns the contact id.
     */
    protected function makeContact(int $channelId, ?string $externalContactId = null, string $displayName = 'Jane'): int
    {
        $db  = db_connect();
        $now = gmdate('Y-m-d H:i:s');
        $db->table('contacts')->insert([
            'channel_id'          => $channelId,
            'external_contact_id' => $externalContactId ?? ('ext_' . uniqid()),
            'display_name'        => $displayName,
            'created_at'          => $now,
        ]);

        return (int) $db->insertID();
    }

    /**
     * Insert a conversation. Returns the conversation id.
     */
    protected function makeConversation(int $channelId, int $contactId, string $lastMessageAt): int
    {
        $db = db_connect();
        $db->table('conversations')->insert([
            'channel_id'      => $channelId,
            'contact_id'      => $contactId,
            'status'          => 'open',
            'last_message_at' => $lastMessageAt,
            'unread_count'    => 0,
        ]);

        return (int) $db->insertID();
    }

    /**
     * Convenience: a full business with one channel, one contact, one conversation.
     *
     * @return array{business_id:int,user_id:int,channel_id:int,contact_id:int,conversation_id:int}
     */
    protected function makeFullBusiness(string $email, string $password, string $lastMessageAt): array
    {
        $businessId     = $this->makeBusiness('Biz ' . $email);
        $userId         = $this->makeUser($businessId, $email, $password);
        $channelId      = $this->makeChannel($businessId);
        $contactId      = $this->makeContact($channelId);
        $conversationId = $this->makeConversation($channelId, $contactId, $lastMessageAt);

        return [
            'business_id'     => $businessId,
            'user_id'         => $userId,
            'channel_id'      => $channelId,
            'contact_id'      => $contactId,
            'conversation_id' => $conversationId,
        ];
    }

    /**
     * Build a valid bearer token for a user identity.
     */
    protected function tokenFor(int $userId, int $businessId, string $role = 'owner'): string
    {
        return (new JwtService())->encode([
            'sub'         => $userId,
            'business_id' => $businessId,
            'role'        => $role,
            'iat'         => time(),
            'exp'         => time() + 3600,
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function authHeaders(string $token): array
    {
        return ['Authorization' => 'Bearer ' . $token];
    }
}
