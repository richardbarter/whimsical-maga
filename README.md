# Whimsical MAGA

> A whimsical archive of outrageous quotes, comments, and actions from Trump, his administration, and republicans — presented against uplifting, colorful backgrounds for maximum ironic contrast.

## What It Is

A rotating quote display site that serves as both a humorous and serious political archive. Serious content, whimsical presentation. Built to be shareable.

## Tech Stack

- **Backend:** Laravel 12 (PHP 8.4)
- **Frontend:** Vue 3 + TypeScript
- **Bridge:** Inertia.js v2 (monorepo — no separate API)
- **Styling:** Tailwind CSS v4 + shadcn-vue components
- **Database:** PostgreSQL
- **Testing:** PHPUnit

## Local Setup

```bash
# Clone and install dependencies
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Configure your .env with PostgreSQL credentials, then:
php artisan migrate --seed

# Start development server (runs Laravel, queue, logs, and Vite concurrently)
composer dev
```

## Key Commands

```bash
composer dev          # Start full dev environment
composer test         # Run all tests
npm run build         # Build frontend assets (includes TypeScript check)
./vendor/bin/pint     # Fix PHP code style
```

## Project Structure

```
app/
  Http/Controllers/Admin/   # Admin CRUD controllers
  Http/Requests/Admin/       # Form request validation
  Models/                    # Quote, Speaker, Tag, Category, Background, User
  Services/                  # Business logic (e.g. QuoteService)

resources/js/
  Pages/Admin/               # Admin panel pages
  Pages/Public/              # Public-facing pages
  Pages/Auth/                # Login etc.
  Components/ui/             # shadcn-vue component library
  composables/               # Reusable Vue composables
  types/                     # TypeScript interfaces

docs/
  whimsical-maga-quips-spec.md   # Full project specification
  database-erd.md                 # Database entity relationship diagram
```

## Roadmap

| Phase | Status | Description |
|-------|--------|-------------|
| Phase 1 — MVP | In progress | Quote rotation, background cycling, admin CRUD |
| Phase 2 — Discovery | Planned | Tags/categories on frontend, search, filtering |
| Phase 3 — Community | Planned | Public user accounts, quote submissions, moderation |
| Phase 4+ | Ideas | Voting, social sharing, quote of the day, API |
