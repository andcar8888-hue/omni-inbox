<?php

namespace Tests\Unit\Services;

use App\Services\AuthService;
use Tests\Support\ApiTestCase;

/**
 * DB-backed unit tests for AuthService credential verification and token issuance.
 *
 * @internal
 */
final class AuthServiceTest extends ApiTestCase
{
    public function testAttemptLoginSucceedsWithCorrectPassword(): void
    {
        $businessId = $this->makeBusiness('Acme');
        $userId     = $this->makeUser($businessId, 'a@test.com', 'Correct!1', 'owner');

        $result = (new AuthService())->attemptLogin('a@test.com', 'Correct!1');

        $this->assertNotNull($result);
        $this->assertSame($userId, $result['user']['id']);
        $this->assertSame($businessId, $result['user']['business_id']);
        $this->assertSame(AuthService::TOKEN_TTL, $result['expires_in']);
        $this->assertArrayNotHasKey('password_hash', $result['user']);
    }

    public function testAttemptLoginFailsWithWrongPassword(): void
    {
        $businessId = $this->makeBusiness('Acme');
        $this->makeUser($businessId, 'a@test.com', 'Correct!1');

        $this->assertNull((new AuthService())->attemptLogin('a@test.com', 'nope'));
    }

    public function testAttemptLoginFailsForUnknownEmail(): void
    {
        $this->assertNull((new AuthService())->attemptLogin('ghost@test.com', 'whatever'));
    }

    public function testIdentityFromTokenReturnsNormalizedClaims(): void
    {
        $businessId = $this->makeBusiness('Acme');
        $userId     = $this->makeUser($businessId, 'a@test.com', 'Correct!1', 'agent');

        $result   = (new AuthService())->attemptLogin('a@test.com', 'Correct!1');
        $identity = (new AuthService())->identityFromToken($result['token']);

        $this->assertSame(['id' => $userId, 'business_id' => $businessId, 'role' => 'agent'], $identity);
    }

    public function testIdentityFromInvalidTokenReturnsNull(): void
    {
        $this->assertNull((new AuthService())->identityFromToken('garbage.token.value'));
    }
}
