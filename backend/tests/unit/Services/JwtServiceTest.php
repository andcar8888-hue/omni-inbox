<?php

namespace Tests\Unit\Services;

use App\Services\JwtService;
use CodeIgniter\Test\CIUnitTestCase;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

/**
 * @internal
 */
final class JwtServiceTest extends CIUnitTestCase
{
    public function testEncodeThenDecodeRoundTrips(): void
    {
        $jwt   = new JwtService(str_repeat('a', 64));
        $token = $jwt->encode(['sub' => 7, 'business_id' => 3, 'role' => 'agent', 'exp' => time() + 60]);

        $claims = $jwt->decode($token);
        $this->assertSame(7, (int) $claims['sub']);
        $this->assertSame(3, (int) $claims['business_id']);
        $this->assertSame('agent', $claims['role']);
    }

    public function testDecodeRejectsTamperedSignature(): void
    {
        $token = (new JwtService(str_repeat('a', 64)))->encode(['sub' => 1, 'exp' => time() + 60]);

        $this->expectException(SignatureInvalidException::class);
        (new JwtService(str_repeat('b', 64)))->decode($token);
    }

    public function testDecodeRejectsExpiredToken(): void
    {
        $jwt   = new JwtService(str_repeat('a', 64));
        $token = $jwt->encode(['sub' => 1, 'exp' => time() - 10]);

        $this->expectException(ExpiredException::class);
        $jwt->decode($token);
    }
}
