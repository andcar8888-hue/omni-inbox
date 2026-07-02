# Omni-Inbox

Unified inbox for replying to WhatsApp, Facebook Messenger, Instagram DMs, and
Telegram leads from one screen.

See [CLAUDE.md](CLAUDE.md) for project context, stack rules, and domain rules.

## Stack

- **Frontend:** React (Vite) + Tailwind — `frontend/`
- **Backend:** CodeIgniter 4 (PHP 8.1+), REST API — `backend/`
- **Database:** MySQL 8

## Prerequisites

- PHP 8.1+ and [Composer](https://getcomposer.org/)
- Node.js 18+ and npm
- MySQL 8

## Backend setup

```bash
cd backend
composer install
cp .env.example .env
php spark key:generate
```

Edit `.env` and point `database.default.*` at your local MySQL 8 instance
(defaults to database `omni_inbox`, user `root`).

Run migrations once they exist under `app/Database/Migrations`:

```bash
php spark migrate
```

Start the dev server:

```bash
php spark serve
```

The API is served at `http://localhost:8080/`.

Run the backend test suite:

```bash
composer test
```

The suite runs against the dedicated **test** database (`omni_inbox_test`,
configured under `database.tests.*` in `.env`). Under PHPUnit the environment is
`testing`, so the `tests` DB group is used automatically — the live `default`
database is never touched. Migrate the test DB schema once before running tests:

```bash
# Migrates the tests group (omni_inbox_test) using the app's testing environment.
CI_ENVIRONMENT=testing vendor/bin/phpunit --filter nothing >/dev/null 2>&1 || true
# Recommended: run migrations against the tests group.
php spark migrate --env testing
```

> Note: `php spark migrate` for the test DB is finicky because spark CLI does
> not always apply the `.env` `database.tests.*` override to the `tests` group.
> If the test tables end up missing, the reliable path is to let PHPUnit's
> bootstrap (which correctly resolves `tests` -> `omni_inbox_test`) run the
> migrations — the CI helper in `tests/_support` handles this, or simply confirm
> tables exist with your MySQL client.

## Test login credentials

For local development, seed one test business and one owner user so you have
something to log in with:

```bash
cd backend
php spark db:seed DevLoginSeeder
```

This runs against your **dev** (`default`) database and is idempotent — re-running
it will not create duplicates.

| Field    | Value          |
|----------|----------------|
| Email    | `owner@test.com` |
| Password | `OmniDev!2026`   |
| Role     | `owner`          |

Log in via `POST /api/v1/auth/login` with that email/password to receive a JWT,
then send it as `Authorization: Bearer <token>` on the protected endpoints
(see [docs/api-contract.md](docs/api-contract.md)).

## Frontend setup

```bash
cd frontend
npm install
cp .env.example .env   # optional; the default already points at local backend
npm run dev
```

The app is served at `http://localhost:5173/` and talks to the backend REST API.

### Environment

The frontend reads the API base URL from the Vite env var `VITE_API_BASE_URL`
(see `frontend/.env.example`). It defaults to `http://localhost:8080/api/v1`,
which matches `php spark serve`, so no `.env` is required for local dev. Point
it elsewhere (e.g. a staging API) by setting the var in `frontend/.env`.

Because the frontend (`:5173`) and backend (`:8080`) are different origins, the
backend enables CORS for `http://localhost:5173` on the `/api/v1/*` routes
(`backend/app/Config/Cors.php` + `Filters.php`).

### Logging in

Start the backend (`php spark serve`) and seed the dev user
(`php spark db:seed DevLoginSeeder`), then sign in with the credentials in the
"Test login credentials" section above. The JWT is stored in `localStorage`;
an expired or invalid token on any request logs you out back to the sign-in
screen automatically.

Build for production:

```bash
npm run build
```

## Repo layout

See [CLAUDE.md](CLAUDE.md) for folder ownership conventions
(`backend/app/Services/Channels/*` for platform adapters,
`frontend/src/components/inbox/*` for the inbox UI).
