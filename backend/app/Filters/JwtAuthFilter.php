<?php

namespace App\Filters;

use App\Services\AuthService;
use App\Services\ResponseFormatter;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Validates the `Authorization: Bearer <token>` header on protected routes.
 *
 * On success it stores the decoded identity (id, business_id, role) in the
 * shared `authContext` service, which controllers read via
 * `service('authContext')->getUser()`.
 *
 * On any failure (missing/malformed/invalid/expired token) it short-circuits
 * with a 401 JSON error and the route handler never runs.
 */
class JwtAuthFilter implements FilterInterface
{
    /**
     * @param array<string, mixed>|null $arguments
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $header = $request->getHeaderLine('Authorization');

        if ($header === '' || ! preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return $this->unauthorized('missing_token', 'Authorization bearer token is required.');
        }

        $token    = trim($matches[1]);
        $identity = (new AuthService())->identityFromToken($token);

        if ($identity === null) {
            return $this->unauthorized('invalid_token', 'The access token is invalid or has expired.');
        }

        // Make the authenticated identity available to controllers via the
        // shared AuthContext service (avoids a deprecated dynamic property on
        // the Request object).
        service('authContext')->setUser($identity);
    }

    /**
     * @param array<string, mixed>|null $arguments
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // no-op
    }

    private function unauthorized(string $code, string $message): ResponseInterface
    {
        return service('response')
            ->setStatusCode(401)
            ->setJSON(ResponseFormatter::error($code, $message));
    }
}
