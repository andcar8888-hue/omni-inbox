<?php

namespace App\Services;

use App\Models\UserModel;

/**
 * Owns authentication business logic: credential verification, JWT issuance,
 * and pulling the authenticated identity back out of a request.
 *
 * Controllers stay thin by delegating here.
 */
class AuthService
{
    /**
     * Token lifetime in seconds (8 hours). Kept as a constant so tests and
     * docs can reference the same value.
     */
    public const TOKEN_TTL = 8 * 3600;

    private UserModel $users;
    private JwtService $jwt;

    public function __construct(?UserModel $users = null, ?JwtService $jwt = null)
    {
        $this->users = $users ?? new UserModel();
        $this->jwt   = $jwt ?? new JwtService();
    }

    /**
     * Verify email + password. On success returns a signed JWT plus the public
     * user fields. On failure returns null (caller decides the HTTP response).
     *
     * @return array{token: string, expires_in: int, user: array<string, mixed>}|null
     */
    public function attemptLogin(string $email, string $password): ?array
    {
        $user = $this->users->where('email', $email)->first();

        if ($user === null) {
            return null;
        }

        if (! password_verify($password, (string) $user['password_hash'])) {
            return null;
        }

        $token = $this->issueToken($user);

        return [
            'token'      => $token,
            'expires_in' => self::TOKEN_TTL,
            'user'       => [
                'id'          => (int) $user['id'],
                'business_id' => (int) $user['business_id'],
                'name'        => $user['name'],
                'email'       => $user['email'],
                'role'        => $user['role'],
            ],
        ];
    }

    /**
     * Build and sign a JWT for the given user row.
     *
     * @param array<string, mixed> $user
     */
    public function issueToken(array $user): string
    {
        $now = time();

        return $this->jwt->encode([
            'sub'         => (int) $user['id'],
            'business_id' => (int) $user['business_id'],
            'role'        => $user['role'],
            'iat'         => $now,
            'exp'         => $now + self::TOKEN_TTL,
        ]);
    }

    /**
     * Verify a bearer token and return the normalized identity, or null if the
     * token is missing/invalid/expired.
     *
     * @return array{id: int, business_id: int, role: string}|null
     */
    public function identityFromToken(string $token): ?array
    {
        try {
            $claims = $this->jwt->decode($token);
        } catch (\Throwable) {
            return null;
        }

        if (! isset($claims['sub'], $claims['business_id'], $claims['role'])) {
            return null;
        }

        return [
            'id'          => (int) $claims['sub'],
            'business_id' => (int) $claims['business_id'],
            'role'        => (string) $claims['role'],
        ];
    }

    /**
     * Read the identity the JwtAuthFilter stored in the shared AuthContext.
     *
     * @return array{id: int, business_id: int, role: string}|null
     */
    public static function currentUser(): ?array
    {
        return service('authContext')->getUser();
    }
}
