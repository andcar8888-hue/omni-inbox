# Omni-Inbox API Contract — `/api/v1`

REST, JSON, versioned. All timestamps are UTC (`Y-m-d H:i:s`). The frontend
converts to local time.

## Response envelopes

Every response uses one of two shapes.

**Success:**

```json
{ "data": <payload> }
```

**Error:**

```json
{
  "error": {
    "code": "machine_readable_code",
    "message": "Human readable message.",
    "details": { "field": ["error 1", "error 2"] }
  }
}
```

`details` is present only for validation errors. The HTTP status carries the
coarse category; `error.code` carries the specific reason.

| HTTP | When |
|------|------|
| 200  | OK |
| 201  | Resource created |
| 401  | Missing / invalid / expired token, or bad login credentials |
| 404  | Resource not found or not owned by the caller's business |
| 422  | Request body failed validation |

## Authentication

Protected endpoints require a bearer token:

```
Authorization: Bearer <jwt>
```

The JWT is HS256, signed with `JWT_SECRET`. Claims: `sub` (user id),
`business_id`, `role`, `iat`, `exp`. Default lifetime: 8 hours.

Error codes from the auth filter: `missing_token`, `invalid_token`.

---

## POST `/api/v1/auth/login`

Public. Exchanges credentials for a JWT.

**Request body**

```json
{ "email": "owner@test.com", "password": "OmniDev!2026" }
```

**Validation**

| Field    | Rules |
|----------|-------|
| email    | required, valid_email, max 191 |
| password | required, string |

**200 response**

```json
{
  "data": {
    "token": "<jwt>",
    "expires_in": 28800,
    "user": {
      "id": 1,
      "business_id": 1,
      "name": "Test Owner",
      "email": "owner@test.com",
      "role": "owner"
    }
  }
}
```

**Errors**

| Status | code               | When |
|--------|--------------------|------|
| 422    | validation_error   | Missing/invalid email or password field |
| 401    | invalid_credentials | Unknown email OR wrong password (same response, no account enumeration) |

---

## GET `/api/v1/conversations`

Protected. Lists all conversations belonging to the authenticated user's
business, ordered by `last_message_at` DESC.

**200 response**

```json
{
  "data": [
    {
      "id": 42,
      "channel_id": 3,
      "contact_id": 9,
      "assigned_user_id": null,
      "status": "open",
      "last_message_at": "2026-06-01 10:00:00",
      "unread_count": 0,
      "channel_platform": "whatsapp",
      "contact_display_name": "Jane Doe",
      "contact_external_id": "wa_15551234567"
    }
  ]
}
```

Empty state returns `{ "data": [] }`.

**Errors:** 401 (`missing_token` / `invalid_token`).

---

## GET `/api/v1/conversations/{id}/messages`

Protected. Lists messages for a conversation the caller's business owns,
chronological (oldest first).

**200 response**

```json
{
  "data": [
    {
      "id": 100,
      "conversation_id": 42,
      "direction": "inbound",
      "sender_user_id": null,
      "external_message_id": "wamid.ABC",
      "body": "Hi, is this available?",
      "attachments_json": null,
      "status": "delivered",
      "created_at": "2026-06-01 09:00:00"
    }
  ]
}
```

**Errors**

| Status | code                   | When |
|--------|------------------------|------|
| 401    | missing_token / invalid_token | No/invalid token |
| 404    | conversation_not_found | Conversation does not exist OR belongs to another business (not leaked) |

---

## POST `/api/v1/conversations/{id}/messages`

Protected. Sends an outbound reply. No platform integration yet — the message
is stored with `direction=outbound`, `status=sent`, `sender_user_id` = the
authenticated user, `created_at` = current UTC time, and the parent
conversation's `last_message_at` is updated.

**Request body**

```json
{ "body": "Yes, still available!" }
```

**Validation**

| Field | Rules |
|-------|-------|
| body  | required, string, max 65535 |

**201 response**

```json
{
  "data": {
    "id": 101,
    "conversation_id": 42,
    "direction": "outbound",
    "sender_user_id": 1,
    "external_message_id": null,
    "body": "Yes, still available!",
    "attachments_json": null,
    "status": "sent",
    "created_at": "2026-07-02 12:34:56"
  }
}
```

**Errors**

| Status | code                   | When |
|--------|------------------------|------|
| 401    | missing_token / invalid_token | No/invalid token |
| 422    | validation_error       | `body` missing/invalid |
| 404    | conversation_not_found | Conversation does not exist OR belongs to another business (not leaked) |

---

## Inbound platform webhooks (not `/api/v1`, no JWT)

These endpoints are called by the platforms' servers, not by API clients, so
they live at top-level `/webhooks/*` (no JWT, no CORS) and are verified by each
platform's own secret. They are documented in full in
[architecture.md](architecture.md#inbound-webhooks-platform---us); summarised
here for discoverability.

### POST `/webhooks/telegram`

Inbound Telegram Bot API `Update`. **Not** JWT-authenticated: verified by the
`X-Telegram-Bot-Api-Secret-Token` header compared constant-time against
`TELEGRAM_WEBHOOK_SECRET`.

- **200** `{ "data": { "result": "stored" | "duplicate" | "ignored" } }` —
  `stored` = new inbound message persisted; `duplicate` = idempotent replay of
  the same `update_id`; `ignored` = nothing to store (non-text update, bad body,
  or setup issue).
- **401** `{ "error": { "code": "invalid_secret", "message": ... } }` — missing,
  wrong, or unconfigured secret token.
