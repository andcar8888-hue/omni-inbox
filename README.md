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

## Frontend setup

```bash
cd frontend
npm install
npm run dev
```

The app is served at `http://localhost:5173/` and expects the backend API to
be running at `http://localhost:8080/`.

Build for production:

```bash
npm run build
```

## Repo layout

See [CLAUDE.md](CLAUDE.md) for folder ownership conventions
(`backend/app/Services/Channels/*` for platform adapters,
`frontend/src/components/inbox/*` for the inbox UI).
