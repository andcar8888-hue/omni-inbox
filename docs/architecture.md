# Omni-Inbox — Backend Architecture

This document describes how the CodeIgniter 4 backend is structured. Read it
together with [CLAUDE.md](../CLAUDE.md) and [db-schema.sql](db-schema.sql).

## Layering

```
HTTP request
   |
   v
Filters            app/Filters/*        (auth: JWT verification)
   |
   v
Controllers        app/Controllers/Api/V1/*   (thin: parse input, delegate, format JSON)
   |
   v
Services           app/Services/*       (all business logic + data-access orchestration)
   |
   v
Models             app/Models/*         (query builder wrappers, validation rules)
   |
   v
MySQL (InnoDB, utf8mb4)
```

Rules (from CLAUDE.md):

- **Controllers are thin.** They validate/parse the HTTP request, call a
  Service, and render a JSON response. No SQL, no business rules in a
  controller.
- **Business logic lives in `app/Services`.** Services take plain PHP
  arguments (not the Request object) and return plain data or throw typed
  exceptions. This keeps them unit-testable without HTTP.
- **Models are data access only.** No cross-table business decisions.

## Auth (JWT)

- `POST /api/v1/auth/login` verifies `email` + `password` against
  `users.password_hash` (bcrypt via `password_verify`) and issues an HS256 JWT
  signed with `env('JWT_SECRET')`.
- The JWT payload carries `sub` (user id), `business_id`, `role`, `iat`, and
  `exp`. Default lifetime is defined in `AuthService::TOKEN_TTL`.
- `app/Filters/JwtAuthFilter.php` runs `before` protected routes. It reads
  `Authorization: Bearer <token>`, verifies signature + expiry, and stores the
  decoded identity on the shared request via
  `$request->user = ['id'=>.., 'business_id'=>.., 'role'=>..]`. On any failure
  it short-circuits with a 401 JSON error and the route handler never runs.
- Controllers read the authenticated identity through
  `AuthService::currentUser($request)`.

`firebase/php-jwt` is used because CI4 ships no JWT library; encoding/decoding
is wrapped in `App\Services\JwtService` so the third-party dependency is
isolated behind one class.

## Multi-tenant isolation

Every business-scoped query is filtered by the authenticated user's
`business_id`. The tenant boundary reaches conversations/messages through
`channels.business_id`:

```
businesses (id)
   ^
   | business_id
channels (id) ---- conversations (channel_id) ---- messages (conversation_id)
```

- Listing conversations joins `conversations -> channels` and filters on
  `channels.business_id`, ordering by `conversations.last_message_at DESC`.
  This uses the existing `idx_conv_channel_last_message` index and avoids
  N+1: a single joined query returns the list.
- Reading or posting messages first verifies the conversation belongs to the
  caller's business. If it does not, the API returns **404** (not 403) so we
  never leak the existence of another tenant's conversation.

## Consistent JSON shapes

- **Success:** `{ "data": <payload> }` (a `ResponseFormatter::success` helper).
- **Error:** `{ "error": { "code": "<machine_code>", "message": "<human>",
  "details": { ... optional field errors ... } } }` via
  `ResponseFormatter::error`. HTTP status carries the coarse category
  (400/401/404/422/500); `code` carries the specific machine-readable reason.

## Inbound webhooks (platform -> us)

Platform inbound events arrive at top-level routes under `/webhooks/*` — NOT
under `/api/v1`. They are called by the platform's servers, not a logged-in
agent, so they carry **no JWT** and **no CORS**; legitimacy is verified inside
each handler by that platform's own mechanism. Heavy work stays out of the
controller (thin dispatch to a Service), so the handler responds fast.

### `POST /webhooks/telegram`

Inbound Telegram Bot API `Update`. Handled by
`App\Controllers\Webhooks\TelegramWebhook::receive`, which delegates to
`App\Services\TelegramInboundService`.

- **Verification (no JWT):** Telegram has no HMAC body signature. Instead, the
  `secret_token` registered via `setWebhook` is echoed on every delivery in the
  `X-Telegram-Bot-Api-Secret-Token` header. The controller compares it
  **constant-time** (`hash_equals`) against `env('TELEGRAM_WEBHOOK_SECRET')` and
  **fails closed**: a missing header, a wrong value, or an unconfigured secret
  (the placeholder sentinel) all return **401** with
  `{ "error": { "code": "invalid_secret", ... } }` before any work is done.
- **Success shape:** `200` with `{ "data": { "result": "<result>" } }` where
  `result` is one of `stored` (new inbound message persisted), `duplicate`
  (this `update_id` was already processed — idempotent), or `ignored`
  (non-text / non-message update, undecodable body, or a setup problem such as
  no telegram channel configured). Telegram only cares about the `200`; the body
  is for observability/tests.
- **Idempotency:** dedupe on a channel-scoped key `tg:{channel_id}:{update_id}`
  (the `messages.external_message_id` UNIQUE column) BEFORE inserting, so
  Telegram's retries never double-insert.
- **Unread counting:** each stored inbound message atomically increments the
  conversation's `unread_count` (`unread_count + 1` as a SQL expression, not a
  read-then-write) so concurrent deliveries can't clobber the count.
- **Config:** `TELEGRAM_WEBHOOK_SECRET` (inbound verification) and
  `TELEGRAM_BOT_TOKEN` (outbound `sendMessage`) live in `.env`
  (see `.env.example`). Both have "not configured" placeholder sentinels.

## Timestamps

All timestamps are stored in **UTC** (`gmdate('Y-m-d H:i:s')`). The frontend is
responsible for converting to the viewer's timezone. Services never write local
time.

## Testing

- Feature tests use `CIUnitTestCase` + `FeatureTestTrait` + `DatabaseTestTrait`
  and run against the `tests` DB group (`omni_inbox_test`), configured in
  `.env` under `database.tests.*`. Run with `php spark test` or
  `vendor/bin/phpunit` under `CI_ENVIRONMENT=testing`.
- `DatabaseTestTrait` wraps each test in a transaction and rolls back, so the
  test DB stays clean between tests. Migrations are applied once with
  `php spark migrate --env testing`.
```
```
