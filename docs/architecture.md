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
