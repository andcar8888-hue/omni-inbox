<?php

namespace App\Controllers\Webhooks;

use App\Controllers\BaseController;
use App\Services\Exceptions\NoTelegramChannelException;
use App\Services\ResponseFormatter;
use App\Services\TelegramInboundService;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

/**
 * Inbound Telegram Bot API webhook.
 *
 * Routed at POST /webhooks/telegram — a top-level route, NOT under /api/v1: no
 * JWT filter (Telegram's servers call this, not a logged-in agent) and no CORS
 * (not a browser origin).
 *
 * Verification: Telegram's documented mechanism is the `secret_token` you set via
 * setWebhook, which Telegram echoes on every delivery in the
 * `X-Telegram-Bot-Api-Secret-Token` header. We compare it (constant-time) to the
 * configured TELEGRAM_WEBHOOK_SECRET and reject mismatches with 401 before doing
 * any work. Telegram has no HMAC body signature (unlike WhatsApp/Meta).
 *
 * Fast + synchronous: there is no queue infra yet. A single text update is cheap
 * to persist, so we process inline. We deliberately do NOT call Telegram's API
 * back from inside this handler (that would be the slow thing to avoid).
 *
 * Idempotency + attribution + parsing all live in TelegramInboundService, keeping
 * this controller thin (CLAUDE.md layering).
 *
 * ADR — "no telegram channel configured": Telegram retries failed deliveries
 * relentlessly. If the app has no telegram channel row, the message can never be
 * attributed no matter how many times Telegram retries — so we log the setup
 * problem loudly and return 200 to stop the retry storm, rather than 5xx. The
 * missing channel is fixed by seeding/onboarding, not by redelivery.
 */
class TelegramWebhook extends BaseController
{
    private const SECRET_HEADER = 'X-Telegram-Bot-Api-Secret-Token';

    /**
     * POST /webhooks/telegram
     */
    public function receive(): ResponseInterface
    {
        if (! $this->secretMatches()) {
            log_message('warning', 'TelegramWebhook: rejected request with missing/invalid secret token.');

            return $this->response
                ->setStatusCode(401)
                ->setJSON(ResponseFormatter::error('invalid_secret', 'Invalid webhook secret token.'));
        }

        $payload = $this->decodeBody();
        if ($payload === null) {
            // Malformed JSON. Nothing to process; 200 so Telegram does not retry
            // a body it will only ever re-send identically.
            log_message('warning', 'TelegramWebhook: received a request with an undecodable JSON body.');

            return $this->ok('ignored');
        }

        try {
            $result = (new TelegramInboundService())->handleUpdate($payload);
        } catch (NoTelegramChannelException $e) {
            // Setup problem — see ADR above. Log loudly, 200 anyway.
            log_message('critical', 'TelegramWebhook: ' . $e->getMessage());

            return $this->ok('ignored');
        } catch (Throwable $e) {
            // Unexpected failure. Do NOT dump the raw payload (general practice).
            // 200 to avoid a retry storm on a bug we would only reproduce.
            log_message('error', 'TelegramWebhook: unexpected error handling update: ' . $e->getMessage());

            return $this->ok('ignored');
        }

        return $this->ok($result['result']);
    }

    /**
     * Constant-time comparison of the configured secret against the header
     * Telegram sends. Returns false if either is empty.
     */
    private function secretMatches(): bool
    {
        $configured = (string) env('TELEGRAM_WEBHOOK_SECRET');
        if ($configured === '' || $configured === 'REPLACE_WITH_REAL_WEBHOOK_SECRET') {
            // Misconfigured server: fail closed rather than accept everything.
            log_message('error', 'TelegramWebhook: TELEGRAM_WEBHOOK_SECRET is not configured.');

            return false;
        }

        $received = (string) $this->request->getHeaderLine(self::SECRET_HEADER);
        if ($received === '') {
            return false;
        }

        return hash_equals($configured, $received);
    }

    /**
     * Decode the JSON request body into an array, or null if it is not decodable.
     *
     * @return array<string, mixed>|null
     */
    private function decodeBody(): ?array
    {
        $raw = (string) $this->request->getBody();
        if ($raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Standard 200 acknowledgement. Body echoes the processing result for
     * observability/tests; Telegram only cares about the 200 status.
     */
    private function ok(string $result): ResponseInterface
    {
        return $this->response
            ->setStatusCode(200)
            ->setJSON(ResponseFormatter::success(['result' => $result]));
    }
}
