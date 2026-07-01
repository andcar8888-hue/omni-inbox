<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Services\ResponseFormatter;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Shared helpers for the versioned JSON API controllers. Keeps controllers thin
 * and guarantees every response uses the consistent envelope shapes.
 */
abstract class BaseApiController extends BaseController
{
    /**
     * @param mixed $data
     */
    protected function respondSuccess($data, int $status = 200): ResponseInterface
    {
        return $this->response
            ->setStatusCode($status)
            ->setJSON(ResponseFormatter::success($data));
    }

    /**
     * @param array<string, mixed> $details
     */
    protected function respondError(int $status, string $code, string $message, array $details = []): ResponseInterface
    {
        return $this->response
            ->setStatusCode($status)
            ->setJSON(ResponseFormatter::error($code, $message, $details));
    }

    /**
     * Decode the JSON request body into an associative array. Returns [] when
     * the body is empty or not valid JSON (validation then reports the missing
     * required fields).
     *
     * @return array<string, mixed>
     */
    protected function jsonInput(): array
    {
        $raw = (string) $this->request->getBody();

        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }
}
