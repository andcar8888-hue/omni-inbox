# Omni-Inbox — Project Context

## What this is
A unified inbox so a business can reply to WhatsApp, Messenger, Instagram,
Telegram, and TikTok leads from one screen. Inbound messages arrive via webhooks, get
normalized into a common schema, stored in MySQL, and shown in a React inbox.
Outbound replies go back out through the matching platform's API.

## Stack rules (non-negotiable)
- Frontend: React + Vite + Tailwind. No Next.js, no Redux — use React Query
  for server state and Zustand only if local state actually needs it.
- Backend: CodeIgniter 4, PHP 8.1+. Follow CI4's MVC + Services pattern.
  Business logic lives in `app/Services`, not in controllers.
- DB: MySQL 8, InnoDB, utf8mb4. All schema changes go through CI4 migrations,
  never hand-edited on a live DB.
- Auth: JWT for the API (agents/admin users), signed webhook verification per
  platform (HMAC signatures — never trust an unverified webhook body).
- API style: REST, JSON, versioned under `/api/v1/`.

## Non-negotiable domain rules
- A "conversation" is unique per (business_channel_id, external_contact_id).
  Never merge two customers into one conversation just because names match.
- Every inbound message must be idempotent — platforms retry webhooks, so
  dedupe on the platform's own message ID before inserting.
- Never log or store full webhook payloads with access tokens in plaintext.
- All timestamps stored in UTC; convert in the frontend only.

## Definition of done for any feature
1. Migration (if schema changed) + rollback tested.
2. Backend endpoint has a validation layer and a PHPUnit test.
3. Frontend component has a loading, empty, and error state — not just happy path.
4. qa-engineer subagent has reviewed it before it's considered mergeable.

## Folder ownership
- `backend/app/Services/Channels/*` = one file per platform adapter, same
  interface: `sendMessage()`, `normalizeIncoming()`. Supported platforms:
  WhatsApp, Messenger, Instagram, Telegram, and TikTok (TikTok adapter lands
  in Phase 5 — see the blocker note below).
- `frontend/src/components/inbox/*` = the 3-pane inbox UI from the reference
  screenshot (conversation list, active thread, contact panel).

## Known dependencies / blockers
- ⚠️ **TikTok messaging requires an approved TikTok for Business partnership.**
  Unlike WhatsApp, Messenger, Instagram, and Telegram — which have no such
  gate — TikTok's messaging API is only accessible once the TikTok for Business
  partner application is approved. This is an **external/business dependency,
  not an engineering task**, and it must be resolved before Phase 5's TikTok
  integration work can actually ship. The `tiktok` platform value exists in the
  schema/model ahead of that approval, but the `Services/Channels` TikTok
  adapter cannot be exercised against the live API until the partnership clears.
