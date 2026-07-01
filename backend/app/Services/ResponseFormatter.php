<?php

namespace App\Services;

/**
 * Builds the consistent JSON envelopes used across the API.
 *
 * Success: { "data": <payload> }
 * Error:   { "error": { "code": "<machine_code>", "message": "<human>", "details": {...} } }
 *
 * Controllers use these so every endpoint returns the same shape (CLAUDE.md rule 5).
 */
class ResponseFormatter
{
    /**
     * @param mixed $data
     *
     * @return array{data: mixed}
     */
    public static function success($data): array
    {
        return ['data' => $data];
    }

    /**
     * @param array<string, mixed> $details
     *
     * @return array{error: array{code: string, message: string, details?: array<string, mixed>}}
     */
    public static function error(string $code, string $message, array $details = []): array
    {
        $error = [
            'code'    => $code,
            'message' => $message,
        ];

        if ($details !== []) {
            $error['details'] = $details;
        }

        return ['error' => $error];
    }
}
