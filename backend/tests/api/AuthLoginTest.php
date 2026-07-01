<?php

namespace Tests\Api;

use App\Services\JwtService;
use Tests\Support\ApiTestCase;

/**
 * @internal
 */
final class AuthLoginTest extends ApiTestCase
{
    public function testSuccessfulLoginReturnsJwtAndUser(): void
    {
        $businessId = $this->makeBusiness('Acme');
        $userId     = $this->makeUser($businessId, 'owner@acme.test', 'Secret!123', 'owner');

        $result = $this->withBodyFormat('json')->post('/api/v1/auth/login', [
            'email'    => 'owner@acme.test',
            'password' => 'Secret!123',
        ]);

        $result->assertStatus(200);
        $result->assertJSONFragment(['data' => ['user' => ['id' => $userId]]]);

        $body = json_decode($result->getJSON(), true);
        $this->assertArrayHasKey('token', $body['data']);
        $this->assertSame($businessId, $body['data']['user']['business_id']);
        $this->assertSame('owner', $body['data']['user']['role']);
        $this->assertArrayNotHasKey('password_hash', $body['data']['user']);

        // The issued token decodes and carries the expected claims.
        $claims = (new JwtService())->decode($body['data']['token']);
        $this->assertSame($userId, (int) $claims['sub']);
        $this->assertSame($businessId, (int) $claims['business_id']);
        $this->assertArrayHasKey('exp', $claims);
    }

    public function testLoginWithWrongPasswordIsRejected(): void
    {
        $businessId = $this->makeBusiness('Acme');
        $this->makeUser($businessId, 'owner@acme.test', 'Secret!123', 'owner');

        $result = $this->withBodyFormat('json')->post('/api/v1/auth/login', [
            'email'    => 'owner@acme.test',
            'password' => 'wrong-password',
        ]);

        $result->assertStatus(401);
        $result->assertJSONFragment(['error' => ['code' => 'invalid_credentials']]);
    }

    public function testLoginWithUnknownEmailIsRejected(): void
    {
        $result = $this->withBodyFormat('json')->post('/api/v1/auth/login', [
            'email'    => 'nobody@acme.test',
            'password' => 'whatever',
        ]);

        $result->assertStatus(401);
        $result->assertJSONFragment(['error' => ['code' => 'invalid_credentials']]);
    }

    public function testLoginValidationFailsWithoutEmail(): void
    {
        $result = $this->withBodyFormat('json')->post('/api/v1/auth/login', [
            'password' => 'whatever',
        ]);

        $result->assertStatus(422);
        $result->assertJSONFragment(['error' => ['code' => 'validation_error']]);
    }
}
