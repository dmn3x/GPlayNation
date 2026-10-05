# GPlayNation

GPlayNation is a storefront for Indonesian local games: players browse and buy games,
developers upload and manage their catalogue, and admins verify studios and moderate releases.
Built with Laravel 12 + Blade + Tailwind CSS (v4) + Vite.

## Requirements

- Docker (PHP tooling runs through the `gpn-php:8.4` image, `composer install` through the
  `composer` image — no local PHP or Composer needed)
- Node.js (only for building frontend assets, optional if you serve prebuilt assets)

Build the PHP image once from the repo root:

```bash
docker build -f docker/Dockerfile -t gpn-php:8.4 .
```

## Setup

From the repo root (`GPlayNation/`), the app lives in `gpn/`:

```bash
cd gpn
docker run --rm -v "$PWD":/app -w /app composer install
cp .env.example .env
docker run --rm -v "$PWD":/app -w /app gpn-php:8.4 php artisan key:generate
docker run --rm -v "$PWD":/app -w /app gpn-php:8.4 php artisan migrate --seed
docker run --rm -v "$PWD":/app -w /app gpn-php:8.4 php artisan storage:link
npm ci
npm run build
```

Serve the app:

```bash
docker run --rm -v "$PWD":/app -w /app -p 8000:8000 gpn-php:8.4 php artisan serve --host=0.0.0.0 --port=8000
```

Then open http://localhost:8000.

## Demo accounts

`php artisan migrate --seed` creates one user:

| Email            | Password   | Role |
|------------------|------------|------|
| test@example.com | `password` | user |

More roles for trying the portals:

- **Developer** — register at `/register` with account type "developer"; the studio stays
  pending until an admin verifies it (`/admin/verification`).
- **Admin** — create one via tinker:
  `User::factory()->admin()->create(['email' => 'admin@example.com'])` (password `password`).

## Tests

```bash
docker run --rm -v "$PWD":/app -w /app gpn-php:8.4 php artisan test
docker run --rm -v "$PWD":/app -w /app gpn-php:8.4 vendor/bin/pint --test
```
