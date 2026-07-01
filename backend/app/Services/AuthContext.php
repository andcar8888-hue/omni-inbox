<?php

namespace App\Services;

/**
 * Per-request holder for the authenticated identity.
 *
 * The JwtAuthFilter populates this after verifying the bearer token; controllers
 * read it back via AuthService::currentUser(). Registered as a SHARED service
 * (see Config\Services::authContext) so the filter and the controller see the
 * same instance within one request.
 *
 * This avoids setting a dynamic property on the HTTP Request object (deprecated
 * in PHP 8.2+), which is the CI4-idiomatic way to pass request-scoped state.
 */
class AuthContext
{
    /** @var array{id: int, business_id: int, role: string}|null */
    private ?array $user = null;

    /**
     * @param array{id: int, business_id: int, role: string} $user
     */
    public function setUser(array $user): void
    {
        $this->user = $user;
    }

    /**
     * @return array{id: int, business_id: int, role: string}|null
     */
    public function getUser(): ?array
    {
        return $this->user;
    }

    public function clear(): void
    {
        $this->user = null;
    }
}
