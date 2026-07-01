<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use RuntimeException;
use Throwable;

/**
 * Thin wrapper around firebase/php-jwt so the third-party dependency is
 * isolated behind one class. CI4 ships no JWT library, so this is the CI4-idiomatic
 * approach: a Service that owns the integration.
 */
class JwtService
{
    private const ALG = 'HS256';

    private string $secret;

    public function __construct(?string $secret = null)
    {
        $secret = $secret ?? (string) env('JWT_SECRET');

        if ($secret === '') {
            throw new RuntimeException('JWT_SECRET is not configured.');
        }

        $this->secret = $secret;
    }

    /**
     * Encode a payload into a signed HS256 JWT string.
     *
     * @param array<string, mixed> $claims
     */
    public function encode(array $claims): string
    {
        return JWT::encode($claims, $this->secret, self::ALG);
    }

    /**
     * Decode and verify a JWT string. Returns the claims as an associative array.
     *
     * @return array<string, mixed>
     *
     * @throws Throwable when the token is malformed, has a bad signature, or is expired.
     */
    public function decode(string $token): array
    {
        $decoded = JWT::decode($token, new Key($this->secret, self::ALG));

        return (array) $decoded;
    }
}
