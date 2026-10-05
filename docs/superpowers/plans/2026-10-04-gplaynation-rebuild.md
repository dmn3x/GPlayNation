# GPlayNation Rebuild Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rebuild the GPlayNation marketplace (SRS + Figma) as a fresh Laravel 12 + Blade + Tailwind 4 app with a simulated payment gateway, deployed to Docker, fully tested and error-free.

**Architecture:** Server-rendered Blade over Laravel 12, SQLite dev/test (MySQL-portable schema), thin controllers with domain logic in `app/Services` (settlement, commission, payout, reports). Payments are simulated in-process through a signed-notification pipeline identical in shape to Midtrans webhooks. Domain tables keep SRS Kamus Data names.

**Tech Stack:** Laravel 12, PHP 8.4 (Docker `php:8.4-cli`), Blade, Tailwind CSS 4 + Vite, PHPUnit, SQLite, `@fontsource/inter`.

**Spec:** `docs/superpowers/specs/2026-10-04-gplaynation-rebuild-design.md` — read it along with this plan. Design tokens: `DESIGN-TOKENS.md` (repo root).

## Global Constraints

- No external HTTP calls anywhere (D2): no CDNs, no OAuth, no real Midtrans, no Google Fonts. Inter via `@fontsource/inter` only.
- No macOS title bar, no traffic dots, no rounded "window" shell anywhere in the UI (D5).
- Blade only — no Livewire/Inertia/JS frameworks. Vanilla JS islands only where specified.
- Money: `decimal(12,2)` columns, `'decimal:2'` casts (strings in PHP), display via `App\Support\Money::format()` → `Rp 1.500.000`.
- Status columns are `string` in migrations; enum-ness via PHP backed-enum casts in models.
- Domain tables use SRS names: `transaksis`, `pembayarans`, `komisis`, `komisi_transaksi`. Everything else Laravel-conventional.
- No SQLite-only SQL: use query builder and portable `date()` expressions only.
- UI copy in English (D7). SRS traceability comments (e.g. `// SRS 3.1.3.1`) welcome.
- TDD: failing test first in every task. `php artisan test` green before every commit.
- Every write op ends with a flash message (`->with('success'|'error', ...)`) — SRS "dialog box" requirement.
- Commands below assume: `WORKSPACE=/home/sudomb/Dev/GPlayNation`, `APP=$WORKSPACE/gpn`.
- Artisan/composer run in Docker (host has no PHP):
  - `art() { docker run --rm -v "$APP":/app -w /app php:8.4-cli php artisan "$@"; }`
  - `gpncomposer() { docker run --rm -v "$APP":/app -w /app composer "$@"; }`
- npm runs on host: `npm --prefix "$APP" <cmd>`.
- Git repo root is `WORKSPACE` (not `gpn/`). Commit from `$WORKSPACE` with `git add -A`.

## Review Focus

1. **Webhook with valid signature but `gross_amount` ≠ `transaksis.total`** — must be rejected (422), never settled. Test in Task 7 (`PaymentWebhookTest::amount_mismatch_is_rejected`).
2. **User retries payment after a `failed`/`expired` transaction** — a new checkout for the same item must succeed and settle cleanly. Test in Task 7 (`CheckoutTest::retry_after_failed_payment_creates_new_transaction`).
3. **Duplicate settlement delivery (replay race)** — two success notifications must yield exactly one library row and one `komisi_transaksi` row. Test in Task 7 (`PaymentWebhookTest::double_settlement_is_idempotent`).
4. **Month-boundary report grouping** — rows at 00:00:00 of the first day and 23:59:59 of the last day of a month must be included; neighbors excluded. Test in Task 10 (`ReportsTest::month_filter_includes_boundaries`).
5. **Admin reverses a rejected developer** — rejected accounts appear in the verification queue's Rejected tab and can be approved (status → active, can log in). Test in Task 10 (`VerificationTest::rejected_developer_can_be_reapproved`).

## Task Map

1. Scaffold & tooling → 2. Design system (tokens, layouts, components) → 3. Schema, enums, models, factories → 4. Auth & roles → 5. Storefront (home/store/game/wishlist/download) → 6. Profile & transactions → 7. Payment core & checkout → 8. Developer portal (dashboard/games/IAP/settings/payouts) → 9. Developer sales/promotions/tags → 10. Admin panel → 11. Community → 12. Polish (errors, animations, Pint) → 13. Seeders & demo world → 14. Docker redeploy & final verification.

---

### Task 1: Project scaffold & tooling

**Files:**
- Create: `gpn/` (fresh Laravel 12), `docker/php-uploads.ini`
- Modify: `gpn/.env`, `gpn/.env.example`, `gpn/config/services.php`, `gpn/phpunit.xml`
- Archive: `gpn/` (legacy) → `gpn-legacy/` (already gitignored)

**Interfaces:**
- Produces: `config('gpn.transaction_ttl_hours')` int (default 24), `config('gpn.default_commission')` string `'20.00'`, `config('services.gpn_payment.server_key')` string — all later tasks consume these.

- [ ] **Step 1: Stop the legacy container and archive the old app**

```bash
docker stop gpn-serve 2>/dev/null || true
cd /home/sudomb/Dev/GPlayNation
mv gpn gpn-legacy
```

- [ ] **Step 2: Scaffold fresh Laravel 12**

```bash
cd /home/sudomb/Dev/GPlayNation
docker run --rm -v "$PWD":/app -w /app composer create-project laravel/laravel gpn --no-interaction
```

Expected: `gpn/artisan` exists. (`composer` image is pulled on first run.)

- [ ] **Step 3: Configure environment**

Edit `gpn/.env`: `APP_NAME=GPlayNation`, `APP_URL=http://localhost:8000`, keep `DB_CONNECTION=sqlite`. Ensure `touch gpn/database/database.sqlite`.

Append to **both** `gpn/.env` and `gpn/.env.example`:

```
GPN_PAYMENT_SERVER_KEY=dev-local-payment-key
GPN_TRX_TTL_HOURS=24
```

Append to `gpn/config/services.php` (before the closing `];`):

```php
    'gpn_payment' => [
        'server_key' => env('GPN_PAYMENT_SERVER_KEY'),
    ],
```

Create `gpn/config/gpn.php`:

```php
<?php

return [
    'transaction_ttl_hours' => (int) env('GPN_TRX_TTL_HOURS', 24),
    'default_commission' => '20.00',
];
```

In `gpn/phpunit.xml`, inside `<php>`, ensure these two lines exist (add if missing):

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

- [ ] **Step 4: Frontend dependencies & font**

```bash
npm --prefix gpn install
npm --prefix gpn install @fontsource/inter
```

(Laravel 12 already ships `tailwindcss` + `@tailwindcss/vite` and a `vite.config.js` with the tailwind plugin — verify the plugin line exists: `tailwindcss(),` in `gpn/vite.config.js`.)

Add font imports at the very top of `gpn/resources/css/app.css` (before the tailwind import):

```css
@import '@fontsource/inter/400.css';
@import '@fontsource/inter/500.css';
@import '@fontsource/inter/600.css';
```

- [ ] **Step 5: Docker PHP upload overrides (used by Task 14)**

Create `docker/php-uploads.ini` at repo root:

```ini
upload_max_filesize=64M
post_max_size=64M
memory_limit=256M
```

- [ ] **Step 6: Verify build & tests**

```bash
npm --prefix gpn run build
docker run --rm -v "$PWD/gpn":/app -w /app php:8.4-cli php artisan test
```

Expected: build clean; `Tests: 2 passed` (stock example tests).

- [ ] **Step 7: Commit**

```bash
cd /home/sudomb/Dev/GPlayNation
git add -A
git commit -m "chore: fresh Laravel 12 scaffold with GPlayNation config"
```

---

### Task 2: Design system — tokens, layouts, core components

**Files:**
- Create: `gpn/resources/css/app.css` (replace), `gpn/resources/views/components/app-layout.blade.php`, `gpn/resources/views/components/portal-layout.blade.php`, `gpn/resources/views/components/icon.blade.php`, `gpn/resources/views/components/logo.blade.php`, `gpn/resources/views/components/toasts.blade.php`, `gpn/resources/views/components/area-chart.blade.php`, `gpn/app/View/Components/AreaChart.php`
- Test: `gpn/tests/Feature/DesignSystemTest.php`

**Interfaces:**
- Produces: `<x-app-layout>` (props: `title`), `<x-portal-layout>` (props: `title`, `section` = `developer`|`admin`), `<x-icon name="...">`, `<x-logo>`, `<x-toasts />`, `<x-area-chart :points="$points" />` where `$points = [['label' => string, 'value' => int|float], ...]`. Task 5+ consume all of these.
- Token classes produced (used by every later view): `bg-bg`, `bg-raised`, `bg-elevated`, `border-border`, `border-border-strong`, `text-fg`, `text-link`, `bg-accent`, `bg-accent-hi`, `text-accent`, `border-accent`, `text-danger`, `text-success`, `rounded-input` (10px), `shadow-card`, `shadow-pop`, `shadow-glow`, `shadow-glow-lg`.

- [ ] **Step 1: Write the failing test**

`gpn/tests/Feature/DesignSystemTest.php`:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class DesignSystemTest extends TestCase
{
    public function test_app_layout_renders_nav_and_no_titlebar(): void
    {
        $this->blade('<x-app-layout title="Test">Hello</x-app-layout>')
            ->assertSee('GPlayNation')
            ->assertSee('Store')
            ->assertSee('Community')
            ->assertSee('Hello')
            ->assertDontSee('traffic');
    }

    public function test_portal_layout_renders_sidebar_nav(): void
    {
        $this->blade('<x-portal-layout title="T" section="developer">Body</x-portal-layout>')
            ->assertSee('Developer Tools')
            ->assertSee('Dashboard')
            ->assertSee('Body');
    }

    public function test_icon_component_renders_svg(): void
    {
        $this->blade('<x-icon name="search" class="w-5 h-5" />')
            ->assertSee('<svg', escape: false)
            ->assertSee('w-5 h-5', escape: false);
    }

    public function test_toasts_render_flashed_messages(): void
    {
        session()->flash('success', 'Saved!');
        $this->blade('<x-toasts />')->assertSee('Saved!');
    }

    public function test_area_chart_renders_path_from_points(): void
    {
        $points = [['label' => '1', 'value' => 0], ['label' => '2', 'value' => 10], ['label' => '3', 'value' => 4]];
        $this->blade('<x-area-chart :points="$points" />', ['points' => $points])
            ->assertSee('<path', escape: false)
            ->assertSee('1D4ED8', escape: false);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
docker run --rm -v "$PWD/gpn":/app -w /app php:8.4-cli php artisan test --filter=DesignSystemTest
```

Expected: FAIL — views/components not found.

- [ ] **Step 3: Token stylesheet**

Replace `gpn/resources/css/app.css` (keep the three fontsource imports at top):

```css
@import '@fontsource/inter/400.css';
@import '@fontsource/inter/500.css';
@import '@fontsource/inter/600.css';
@import 'tailwindcss';

@theme {
    --color-bg: #0F0F10;
    --color-raised: #161618;
    --color-elevated: #222224;
    --color-border: #222224;
    --color-border-strong: #27282A;
    --color-fg: #FFFFFF;
    --color-link: #60A5FA;
    --color-danger: #E7000B;
    --color-success: #30D158;
    --color-accent: #1D4ED8;
    --color-accent-hi: #287BF7;
    --radius-input: 10px;
    --shadow-card: 0 8px 10px -6px rgb(0 0 0 / .2);
    --shadow-pop: 0 3px 6px rgb(0 0 0 / .2);
    --shadow-glow: 0 0 15px 0 #1D4ED8;
    --shadow-glow-lg: 0 0 30px 0 #1D4ED8;
    --font-sans: 'Inter', ui-sans-serif, system-ui, sans-serif;
}

@layer base {
    html { color-scheme: dark; }
    body { @apply bg-bg text-fg font-sans antialiased; }
    ::selection { background: rgb(29 78 216 / .4); }
    :focus-visible { outline: none; box-shadow: 0 0 0 1.67px #1D4ED8; border-radius: 4px; }
    ::-webkit-scrollbar { width: 10px; height: 10px; }
    ::-webkit-scrollbar-thumb { background: #27282A; border-radius: 9999px; }
    ::-webkit-scrollbar-track { background: transparent; }
}

@layer components {
    .text-muted { color: rgb(255 255 255 / .6); }
    .text-subtle { color: rgb(255 255 255 / .5); }
    .text-faint { color: rgb(255 255 255 / .4); }
    .text-ghost { color: rgb(255 255 255 / .3); }
    .btn-primary { @apply inline-flex items-center justify-center gap-2 rounded-xl bg-accent px-5 py-2.5 text-[16.2px] font-medium text-white transition hover:bg-accent-hi hover:shadow-glow disabled:opacity-40 disabled:pointer-events-none; }
    .btn-ghost { @apply inline-flex items-center justify-center gap-2 rounded-xl border border-border-strong bg-elevated px-5 py-2.5 text-[16.2px] font-medium text-fg transition hover:border-accent disabled:opacity-40; }
    .input { @apply w-full rounded-input border border-border bg-elevated px-4 py-2.5 text-[16.2px] text-fg placeholder:text-subtle focus:border-accent focus:outline-none; }
    .card { @apply rounded-2xl border border-border-strong bg-raised shadow-card; }
    .nav-pill { @apply rounded-full px-4 py-2 text-[16.2px] font-medium text-subtle transition hover:bg-accent/10 hover:text-fg; }
    .nav-pill-active { @apply bg-accent/10 text-fg; }
    .screen-title { @apply text-[27px] font-semibold leading-[37.8px] tracking-[-0.675px]; }
    .section-heading { @apply text-[18.9px] font-semibold leading-[27px]; }
    .field-error { @apply mt-1 text-sm text-danger; }
}
```

- [ ] **Step 4: Layouts, icon, logo, toasts, chart**

`components/app-layout.blade.php` (storefront shell — nav pills per Figma Home header, guest/auth right side; NO title bar):

```blade
@props(['title' => null])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? "$title · GPlayNation" : 'GPlayNation' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
    <header class="sticky top-0 z-40 border-b border-border bg-bg/90 backdrop-blur">
        <div class="mx-auto flex h-[72px] max-w-[1440px] items-center gap-6 px-12">
            <a href="{{ route('home') }}" class="flex items-center gap-3"><x-logo class="h-9 w-9" /><span class="text-[18.9px] font-semibold">GPlayNation</span></a>
            <nav class="flex items-center gap-1">
                <a href="{{ route('home') }}" @class(['nav-pill', 'nav-pill-active' => request()->routeIs('home')])>Home</a>
                <a href="{{ route('store.index') }}" @class(['nav-pill', 'nav-pill-active' => request()->routeIs('store.*')])>Store</a>
                <a href="{{ route('community.index') }}" @class(['nav-pill', 'nav-pill-active' => request()->routeIs('community.*')])>Community</a>
            </nav>
            <form action="{{ route('store.index') }}" method="GET" class="ml-auto w-72">
                <input name="q" value="{{ request('q') }}" placeholder="Search local games…" class="input !py-2">
            </form>
            @auth
                <a href="{{ route('profile.show') }}" class="flex items-center gap-2 rounded-full px-3 py-1.5 hover:bg-elevated">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-accent text-sm font-semibold">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                    <span class="text-[16.2px] font-medium">{{ auth()->user()->name }}</span>
                </a>
            @else
                <a href="{{ route('login') }}" class="btn-primary !py-2">Sign in</a>
            @endauth
        </div>
    </header>
    <main class="mx-auto max-w-[1440px] px-12 py-10">
        {{ $slot }}
    </main>
    <x-toasts />
</body>
</html>
```

`components/portal-layout.blade.php` (sidebar shell for dev + admin; `section` switches nav):

```blade
@props(['title' => null, 'section' => 'developer'])
@php
    $nav = $section === 'admin'
        ? [['admin.dashboard', 'Dashboard', 'chart'], ['admin.verification.index', 'Verification', 'check'], ['admin.moderation.index', 'Moderation', 'shield'], ['admin.commission.index', 'Commission', 'wallet'], ['admin.payouts.index', 'Payouts', 'banknote'], ['admin.reports.index', 'Reports', 'document']]
        : [['dev.dashboard', 'Dashboard', 'chart'], ['dev.games.index', 'Games', 'gamepad'], ['dev.sales.index', 'Sales', 'banknote'], ['dev.promotion.index', 'Promotion', 'tag'], ['dev.tags.index', 'Tags', 'tags'], ['dev.payouts.index', 'Payouts', 'wallet'], ['dev.settings.edit', 'Settings', 'cog']];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? "$title · GPlayNation" : 'GPlayNation' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
    <div class="flex min-h-screen">
        <aside class="flex w-[220px] shrink-0 flex-col border-r border-border bg-raised p-4">
            <a href="{{ route('home') }}" class="mb-6 flex items-center gap-2 px-2"><x-logo class="h-8 w-8" /><span class="font-semibold">GPlayNation</span></a>
            <p class="mb-2 px-2 text-xs font-medium uppercase tracking-wide text-faint">{{ $section === 'admin' ? 'Administration' : 'Developer Tools' }}</p>
            <nav class="flex flex-col gap-1">
                @foreach ($nav as [$route, $label, $icon])
                    <a href="{{ route($route) }}" @class(['flex items-center gap-3 rounded-lg px-3 py-2 text-[16.2px] font-medium transition text-subtle hover:bg-accent/10 hover:text-fg', 'bg-accent/10 !text-fg' => request()->routeIs($route)])>
                        <x-icon :name="$icon" class="h-5 w-5" /> {{ $label }}
                    </a>
                @endforeach
            </nav>
            <form method="POST" action="{{ route('logout') }}" class="mt-auto">
                @csrf
                <button class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-[16.2px] font-medium text-subtle transition hover:bg-accent/10 hover:text-fg"><x-icon name="logout" class="h-5 w-5" /> Log out</button>
            </form>
        </aside>
        <main class="min-w-0 flex-1 p-10">
            {{ $slot }}
        </main>
    </div>
    <x-toasts />
</body>
</html>
```

`components/icon.blade.php` — one `<svg>` with a name→paths map (24×24, `fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"`). Include names: `home, store(bag), users, user, search, download, heart, wallet, chart, tag, tags, cog, logout, check, x, chevron-down, chevron-left, chevron-right, plus, pencil, trash, upload, arrow-up, arrow-down, gamepad, shield, banknote, document, alert, star`. Implementation shape:

```blade
@props(['name'])
@php
$paths = [
    'search' => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
    'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/>',
    'chart' => '<line x1="12" x2="12" y1="20" y2="10"/><line x1="18" x2="18" y1="20" y2="4"/><line x1="6" x2="6" y1="20" y2="16"/>',
    'check' => '<polyline points="20 6 9 17 4 12"/>',
    'x' => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
    'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
    'chevron-left' => '<path d="m15 18-6-6 6-6"/>',
    'chevron-right' => '<path d="m9 18 6-6-6-6"/>',
    'plus' => '<path d="M5 12h14"/><path d="M12 5v14"/>',
    'pencil' => '<path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>',
    'trash' => '<path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
    'upload' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/>',
    'arrow-up' => '<path d="m5 12 7-7 7 7"/><path d="M12 19V5"/>',
    'arrow-down' => '<path d="M12 5v14"/><path d="m19 12-7 7-7-7"/>',
    'heart' => '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>',
    'wallet' => '<path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/>',
    'banknote' => '<rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/>',
    'tag' => '<path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r=".5"/>',
    'tags' => '<path d="m9 5 7.6 7.6a2.4 2.4 0 0 1 0 3.4l-5.2 5.2a2.4 2.4 0 0 1-3.4 0L2 15V9a2 2 0 0 1 2-2h5z"/><path d="m15 5 6 6"/>',
    'cog' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
    'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/>',
    'home' => '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
    'store' => '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>',
    'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
    'user' => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
    'gamepad' => '<line x1="6" x2="10" y1="11" y2="11"/><line x1="8" x2="8" y1="9" y2="13"/><line x1="15" x2="15.01" y1="12" y2="12"/><line x1="18" x2="18.01" y1="10" y2="10"/><path d="M17.32 5H6.68a4 4 0 0 0-3.98 3.59C2.6 9.42 2 14.46 2 16a3 3 0 0 0 3 3c1 0 1.5-.5 2-1l1.41-1.41A2 2 0 0 1 9.83 16h4.34a2 2 0 0 1 1.41.59L17 18c.5.5 1 1 2 1a3 3 0 0 0 3-3c0-1.54-.6-6.58-.68-7.26A4 4 0 0 0 17.32 5z"/>',
    'shield' => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/>',
    'document' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/>',
    'alert' => '<circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/>',
    'star' => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
];
@endphp
<svg {{ $attributes->merge(['class' => 'h-5 w-5']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $paths[$name] ?? $paths['alert'] !!}</svg>
```

`components/logo.blade.php`:

```blade
<svg {{ $attributes->merge(['class' => 'h-8 w-8']) }} viewBox="0 0 48 48" fill="none" aria-hidden="true">
    <rect width="48" height="48" rx="12" fill="#1D4ED8"/>
    <path d="M14 20h20a6 6 0 0 1 6 6c0 4-2 8-5 8-2 0-3-1-4-2l-2-2h-10l-2 2c-1 1-2 2-4 2-3 0-5-4-5-8a6 6 0 0 1 6-6z" fill="#fff"/>
    <path d="M17 24v6M14 27h6" stroke="#1D4ED8" stroke-width="2" stroke-linecap="round"/>
    <circle cx="30" cy="25" r="1.6" fill="#1D4ED8"/><circle cx="34" cy="29" r="1.6" fill="#1D4ED8"/>
</svg>
```

`components/toasts.blade.php`:

```blade
@if (session('success') || session('error'))
    <div class="fixed bottom-6 right-6 z-50 flex flex-col gap-2" role="status">
        @if (session('success'))
            <div data-toast class="card flex items-center gap-3 !rounded-xl px-4 py-3 border-success/40">
                <x-icon name="check" class="h-5 w-5 text-success" /><span>{{ session('success') }}</span>
                <button type="button" onclick="this.closest('[data-toast]').remove()" aria-label="Dismiss"><x-icon name="x" class="h-4 w-4 text-subtle" /></button>
            </div>
        @endif
        @if (session('error'))
            <div data-toast class="card flex items-center gap-3 !rounded-xl px-4 py-3 border-danger/60">
                <x-icon name="alert" class="h-5 w-5 text-danger" /><span>{{ session('error') }}</span>
                <button type="button" onclick="this.closest('[data-toast]').remove()" aria-label="Dismiss"><x-icon name="x" class="h-4 w-4 text-subtle" /></button>
            </div>
        @endif
    </div>
    <script>setTimeout(() => document.querySelectorAll('[data-toast]').forEach(t => t.remove()), 5000);</script>
@endif
```

`app/View/Components/AreaChart.php`:

```php
<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AreaChart extends Component
{
    public string $line;
    public string $area;
    public float $max;

    /** @param array<int, array{label: string, value: int|float}> $points */
    public function __construct(public array $points, public int $height = 180)
    {
        $w = 720;
        $h = $this->height;
        $this->max = max(1, (float) max(array_map(fn ($p) => $p['value'], $this->points) ?: [0]));
        $n = max(1, count($this->points) - 1);
        $coords = [];
        foreach (array_values($this->points) as $i => $p) {
            $coords[] = [round($i * $w / $n, 1), round($h - 8 - ($p['value'] / $this->max) * ($h - 24), 1)];
        }
        $this->line = implode(' ', array_map(fn ($c) => "$c[0],$c[1]", $coords));
        $this->area = "0,$h " . $this->line . " $w,$h";
    }

    public function render(): View|Closure
    {
        return view('components.area-chart');
    }
}
```

`components/area-chart.blade.php` (1.5px accent line, 15% gradient fill per tokens):

```blade
<div {{ $attributes->merge(['class' => 'card p-5']) }}>
    <svg viewBox="0 0 720 {{ $height }}" class="w-full" preserveAspectRatio="none" role="img" aria-label="Chart">
        <defs>
            <linearGradient id="areaFill" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#1D4ED8" stop-opacity="0.15" />
                <stop offset="100%" stop-color="#1D4ED8" stop-opacity="0" />
            </linearGradient>
        </defs>
        @for ($i = 1; $i <= 3; $i++)
            <line x1="0" x2="720" y1="{{ $height * $i / 4 }}" y2="{{ $height * $i / 4 }}" stroke="#222224" stroke-width="1" />
        @endfor
        <polygon points="{{ $area }}" fill="url(#areaFill)" />
        <polyline points="{{ $line }}" fill="none" stroke="#1D4ED8" stroke-width="1.5" stroke-linejoin="round" stroke-linecap="round" />
    </svg>
    <div class="mt-2 flex justify-between text-xs text-faint">
        @foreach (array_filter($points, fn ($p, $i) => $i % (int) max(1, ceil(count($points) / 7)) === 0, ARRAY_FILTER_USE_BOTH) as $p)
            <span>{{ $p['label'] }}</span>
        @endforeach
    </div>
</div>
```

- [ ] **Step 5: Run tests to verify they pass**

```bash
docker run --rm -v "$PWD/gpn":/app -w /app php:8.4-cli php artisan test --filter=DesignSystemTest
```

Expected: PASS (5 tests). Also `npm --prefix gpn run build` still clean.

- [ ] **Step 6: Commit**

```bash
git add -A && git commit -m "feat: design system — tokens, layouts, icon/logo/toast/chart components"
```

---
### Task 3: Database schema, enums, models, factories

**Files:**
- Modify: `gpn/database/migrations/0001_01_01_000000_create_users_table.php`
- Create: `gpn/database/migrations/2026_01_01_000100_create_gplaynation_core_tables.php`, `..._000200_create_gplaynation_money_tables.php`, `..._000300_create_gplaynation_community_tables.php`
- Create: `gpn/app/Enums/{Role,UserStatus,GameStatus,TransaksiStatus,IapType,PaymentMethod,LibrarySource}.php`
- Create: `gpn/app/Models/{Developer,Game,GameScreenshot,Tag,Iap,Promotion,Transaksi,Pembayaran,Komisi,KomisiTransaksi,Payout,Library,IapOwnership,Wishlist,Post,PostVote,Download}.php`; modify `gpn/app/Models/User.php`
- Create: `gpn/app/Support/Money.php`
- Create: `gpn/database/factories/{DeveloperFactory,GameFactory,TagFactory,IapFactory,PromotionFactory,TransaksiFactory,PembayaranFactory,KomisiFactory,KomisiTransaksiFactory,PayoutFactory,LibraryFactory,IapOwnershipFactory,WishlistFactory,PostFactory,PostVoteFactory,DownloadFactory}.php`; modify `gpn/database/factories/UserFactory.php`
- Test: `gpn/tests/Feature/SchemaTest.php`, `gpn/tests/Unit/MoneyTest.php`

**Interfaces:**
- Produces (consumed everywhere later): all models with relations/casts; `Game::activePromotion(): ?Promotion`, `Game::effectivePrice(): string`, `Game::scopePublished(Builder $q): Builder`, `Game::isFree(): bool`, `Game::ownedBy(?User $u): bool`; `Transaksi::generateOrderId(): string`, `Transaksi::isFinal(): bool`; `User::isAdmin(): bool`, `User::isDeveloper(): bool`, `User::isVerifiedDeveloper(): bool`; `Money::format(string|int|float $amount): string`; enums as listed.

- [ ] **Step 1: Write the failing tests**

`gpn/tests/Unit/MoneyTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_formats_idr_without_decimals(): void
    {
        $this->assertSame('Rp 1.500.000', Money::format('1500000.00'));
        $this->assertSame('Rp 0', Money::format('0.00'));
        $this->assertSame('Rp 49.500', Money::format(49500));
    }
}
```

`gpn/tests/Feature/SchemaTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\{GameStatus, Role, TransaksiStatus, UserStatus};
use App\Models\{Developer, Game, Iap, KomisiTransaksi, Library, Promotion, Transaksi, User};
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_developer_relation_and_role_casts(): void
    {
        $user = User::factory()->developer()->create();
        $this->assertTrue($user->isDeveloper());
        $this->assertSame(UserStatus::Pending, $user->status);
        $this->assertInstanceOf(Developer::class, $user->developer);
        $this->assertFalse($user->isVerifiedDeveloper());
        $user->update(['status' => UserStatus::Active]);
        $this->assertTrue($user->fresh()->isVerifiedDeveloper());
    }

    public function test_game_relations_and_published_scope(): void
    {
        $game = Game::factory()->published()->create();
        Game::factory()->create(['status' => GameStatus::PendingReview]);
        $this->assertSame(1, Game::published()->count());
        $this->assertInstanceOf(Developer::class, $game->developer);
    }

    public function test_effective_price_applies_active_promotion_only(): void
    {
        $game = Game::factory()->published()->create(['price' => '200000.00']);
        $this->assertSame('200000.00', $game->effectivePrice());
        Promotion::factory()->create(['game_id' => $game->id, 'discount_percent' => 25, 'starts_at' => now()->subDay(), 'ends_at' => now()->addDay()]);
        $this->assertSame('150000.00', $game->fresh()->effectivePrice());
        $this->assertInstanceOf(Promotion::class, $game->activePromotion());
    }

    public function test_library_unique_per_user_game(): void
    {
        $row = Library::factory()->create();
        $this->expectException(QueryException::class);
        Library::factory()->create(['user_id' => $row->user_id, 'game_id' => $row->game_id]);
    }

    public function test_order_id_format_and_uniqueness(): void
    {
        $id = Transaksi::generateOrderId();
        $this->assertMatchesRegularExpression('/^GPN-\d{8}-[A-Z0-9]{6}$/', $id);
        Transaksi::factory()->create(['order_id' => $id]);
        $this->assertNotSame($id, Transaksi::generateOrderId());
    }

    public function test_transaksi_is_final_only_for_terminal_states(): void
    {
        $pending = Transaksi::factory()->create(['status' => TransaksiStatus::Pending]);
        $success = Transaksi::factory()->success()->create();
        $this->assertFalse($pending->isFinal());
        $this->assertTrue($success->isFinal());
        $this->assertSame(0, KomisiTransaksi::count()); // table exists, untouched
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
docker run --rm -v "$PWD/gpn":/app -w /app php:8.4-cli php artisan test --filter="SchemaTest|MoneyTest"
```

Expected: FAIL — classes/tables do not exist.

- [ ] **Step 3: Enums**

One file per enum in `gpn/app/Enums/`, all `enum X: string`:

```php
<?php

namespace App\Enums;

enum Role: string
{
    case User = 'user';
    case Developer = 'developer';
    case Admin = 'admin';
}
```

`UserStatus`: `Active = 'active'`, `Pending = 'pending'`, `Rejected = 'rejected'`.
`GameStatus`: `Draft = 'draft'`, `PendingReview = 'pending_review'`, `Published = 'published'`, `Rejected = 'rejected'`, `Archived = 'archived'`.
`TransaksiStatus`: `Pending = 'pending'`, `Success = 'success'`, `Failed = 'failed'`, `Expired = 'expired'`.
`IapType`: `Currency = 'currency'`, `Item = 'item'`, `Upgrade = 'upgrade'`, `Cosmetic = 'cosmetic'`.
`PaymentMethod`: `EWallet = 'e_wallet'`, `VirtualAccount = 'virtual_account'`, `CreditCard = 'credit_card'`.
`LibrarySource`: `Purchase = 'purchase'`, `Free = 'free'`.

- [ ] **Step 4: Migrations**

In `0001_01_01_000000_create_users_table.php`, extend the `users` table schema (keep sessions/password_reset_tokens tables untouched):

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name', 100);
    $table->string('email', 150)->unique();
    $table->string('password');
    $table->string('role', 20)->default('user');
    $table->string('status', 20)->default('active');
    $table->string('avatar_path')->nullable();
    $table->text('rejection_reason')->nullable();
    $table->rememberToken();
    $table->timestamps();
});
```

`2026_01_01_000100_create_gplaynation_core_tables.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('developers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('studio_name', 120);
            $table->text('portfolio')->nullable();
            $table->string('payout_bank', 100)->nullable();
            $table->string('payout_account', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('developer_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('slug')->unique();
            $table->text('description');
            $table->decimal('price', 12, 2)->default(0);
            $table->string('genre', 50);
            $table->string('cover_path');
            $table->string('file_path');
            $table->string('trailer_url')->nullable();
            $table->string('status', 20)->default('draft');
            $table->text('rejection_feedback')->nullable();
            $table->timestamps();
            $table->index(['status', 'genre']);
        });

        Schema::create('game_screenshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->timestamps();
        });

        Schema::create('game_tag', function (Blueprint $table) {
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['game_id', 'tag_id']);
        });

        Schema::create('iaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->string('type', 20)->default('item');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('discount_percent');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->timestamps();
        });

        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'game_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlists');
        Schema::dropIfExists('promotions');
        Schema::dropIfExists('iaps');
        Schema::dropIfExists('game_tag');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('game_screenshots');
        Schema::dropIfExists('games');
        Schema::dropIfExists('developers');
    }
};
```

(`libraries` and `iap_ownerships` live in the **money** migration below, because they FK to `transaksis` — SQLite tolerates forward FK references, MySQL rejects them.)

`2026_01_01_000200_create_gplaynation_money_tables.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksis', function (Blueprint $table) {
            $table->id();
            $table->string('order_id', 30)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('iap_id')->nullable()->constrained('iaps')->nullOnDelete();
            $table->string('type', 10);
            $table->decimal('total', 12, 2);
            $table->string('status', 10)->default('pending');
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index(['game_id', 'status']);
        });

        Schema::create('pembayarans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_id')->constrained('transaksis')->cascadeOnDelete();
            $table->string('method', 20);
            $table->decimal('amount', 12, 2);
            $table->string('reference_no', 100);
            $table->json('payload');
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('komisis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('percentage', 5, 2);
            $table->date('effective_from');
            $table->timestamps();
        });

        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('developer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->string('period', 7);
            $table->decimal('gross', 14, 2);
            $table->decimal('commission', 14, 2);
            $table->decimal('net', 14, 2);
            $table->string('status', 10)->default('processed');
            $table->timestamps();
        });

        // komisi_transaksi AFTER payouts (payout_id FK), libraries/iap_ownerships here (transaksis FK)
        Schema::create('komisi_transaksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_id')->unique()->constrained('transaksis')->cascadeOnDelete();
            $table->foreignId('komisi_id')->constrained('komisis')->cascadeOnDelete();
            $table->decimal('percentage', 5, 2);
            $table->decimal('amount', 12, 2);
            $table->decimal('developer_amount', 12, 2);
            $table->foreignId('payout_id')->nullable()->constrained('payouts')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('libraries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transaksi_id')->nullable()->constrained('transaksis')->nullOnDelete();
            $table->string('source', 10)->default('purchase');
            $table->boolean('is_showcased')->default(true);
            $table->timestamps();
            $table->unique(['user_id', 'game_id']);
        });

        Schema::create('iap_ownerships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('iap_id')->constrained('iaps')->cascadeOnDelete();
            $table->foreignId('transaksi_id')->constrained('transaksis')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'iap_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iap_ownerships');
        Schema::dropIfExists('libraries');
        Schema::dropIfExists('komisi_transaksi');
        Schema::dropIfExists('payouts');
        Schema::dropIfExists('komisis');
        Schema::dropIfExists('pembayarans');
        Schema::dropIfExists('transaksis');
    }
};
```

`2026_01_01_000300_create_gplaynation_community_tables.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->string('image_path')->nullable();
            $table->json('hashtags');
            $table->timestamps();
            $table->index('created_at');
        });

        Schema::create('post_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('value');
            $table->timestamps();
            $table->unique(['post_id', 'user_id']);
        });

        Schema::create('downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('downloads');
        Schema::dropIfExists('post_votes');
        Schema::dropIfExists('posts');
    }
};
```

- [ ] **Step 5: Models + Money helper**

`app/Support/Money.php`:

```php
<?php

namespace App\Support;

class Money
{
    public static function format(string|int|float $amount): string
    {
        return 'Rp ' . number_format((float) $amount, 0, ',', '.');
    }
}
```

`app/Models/User.php` (replace stock):

```php
<?php

namespace App\Models;

use App\Enums\Role;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    protected $fillable = ['name', 'email', 'password', 'role', 'status', 'avatar_path', 'rejection_reason'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'role' => Role::class, 'status' => UserStatus::class];
    }

    public function developer(): HasOne
    {
        return $this->hasOne(Developer::class);
    }

    public function libraries(): HasMany
    {
        return $this->hasMany(Library::class);
    }

    public function iapOwnerships(): HasMany
    {
        return $this->hasMany(IapOwnership::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function transaksis(): HasMany
    {
        return $this->hasMany(Transaksi::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isDeveloper(): bool
    {
        return $this->role === Role::Developer;
    }

    public function isVerifiedDeveloper(): bool
    {
        return $this->isDeveloper() && $this->status === UserStatus::Active;
    }
}
```

Remaining models — each with `$fillable`, casts, and relations exactly as named below (write them all out; no stubs):

- `Developer`: fillable `user_id, studio_name, portfolio, payout_bank, payout_account`; `user(): BelongsTo`, `games(): HasMany`, `payouts(): HasMany`.
- `Game`: fillable `developer_id, name, slug, description, price, genre, cover_path, file_path, trailer_url, status, rejection_feedback`; casts `price => 'decimal:2'`, `status => GameStatus::class`; relations `developer(): BelongsTo`, `screenshots(): HasMany` (ordered `sort`), `tags(): BelongsToMany`, `iaps(): HasMany`, `promotions(): HasMany`, `transaksis(): HasMany`, `downloads(): HasMany`; constant `public const GENRES = ['Action', 'Adventure', 'RPG', 'Strategy', 'Simulation', 'Puzzle', 'Horror', 'Racing', 'Sports', 'Casual'];` — every genre validation rule (`StoreFilterRequest`, `GameStoreRequest`, `GameUpdateRequest`) and genre dropdown uses `Rule::in(Game::GENRES)` / this constant, never a retyped list; methods:

```php
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', GameStatus::Published);
    }

    public function isFree(): bool
    {
        return bccomp($this->price, '0', 2) === 0;
    }

    public function activePromotion(): ?Promotion
    {
        return $this->promotions()
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->latest('starts_at')
            ->first();
    }

    public function effectivePrice(): string
    {
        $promo = $this->activePromotion();
        if (! $promo) {
            return $this->price;
        }

        return bcmul($this->price, bcdiv((string) (100 - $promo->discount_percent), '100', 4), 2);
    }

    public function ownedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->hasOne(Library::class)->where('user_id', $user->id)->exists()
            || $this->isFree();
    }
```

- `GameScreenshot`: fillable `game_id, path, sort`; `game(): BelongsTo`.
- `Tag`: fillable `name`; `games(): BelongsToMany`.
- `Iap`: fillable `game_id, name, description, price, type, is_active`; casts `price => 'decimal:2'`, `type => IapType::class`, `is_active => 'boolean'`; `game(): BelongsTo`; `scopeActive($q) => $q->where('is_active', true)`.
- `Promotion`: fillable `game_id, discount_percent, starts_at, ends_at`; casts datetimes; `game(): BelongsTo`; `scopeActive($q)`.
- `Transaksi`: fillable `order_id, user_id, game_id, iap_id, type, total, status`; casts `total => 'decimal:2'`, `status => TransaksiStatus::class`; relations `user()`, `game()`, `iap()`, `pembayaran(): HasOne`, `komisiTransaksi(): HasOne`; methods:

```php
    public static function generateOrderId(): string
    {
        do {
            $id = 'GPN-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        } while (static::where('order_id', $id)->exists());

        return $id;
    }

    public function isFinal(): bool
    {
        return $this->status !== TransaksiStatus::Pending;
    }
```

- `Pembayaran`: fillable `transaksi_id, method, amount, reference_no, payload, paid_at`; casts `amount => 'decimal:2'`, `method => PaymentMethod::class`, `payload => 'array'`, `paid_at => 'datetime'`; `transaksi(): BelongsTo`.
- `Komisi`: fillable `admin_id, percentage, effective_from`; casts `percentage => 'decimal:2'`, `effective_from => 'date'`; `admin(): BelongsTo` (User).
- `KomisiTransaksi`: table `'komisi_transaksi'`; fillable `transaksi_id, komisi_id, percentage, amount, developer_amount, payout_id`; decimal casts; relations `transaksi()`, `komisi()`, `payout(): BelongsTo`.
- `Payout`: fillable `developer_id, admin_id, period, gross, commission, net, status`; decimal casts; relations `developer()`, `admin()` (User), `komisiTransaksis(): HasMany`.
- `Library`: fillable `user_id, game_id, transaksi_id, source, is_showcased`; casts `source => LibrarySource::class`, `is_showcased => 'boolean'`; relations `user()`, `game()`, `transaksi()`.
- `IapOwnership`: fillable `user_id, iap_id, transaksi_id`; relations `user()`, `iap()`, `transaksi()`.
- `Wishlist`: fillable `user_id, game_id`; relations `user()`, `game()`.
- `Post`: fillable `user_id, body, image_path, hashtags`; casts `hashtags => 'array'`; relations `user()`, `votes(): HasMany`; accessor `getScoreAttribute(): int => $this->votes()->sum('value')`.
- `PostVote`: fillable `post_id, user_id, value`; relations `post()`, `user()`.
- `Download`: `public $timestamps = false`... keep `created_at` only: set `const UPDATED_AT = null`; fillable `user_id, game_id`; relations `user()`, `game()`.

- [ ] **Step 6: Factories**

`UserFactory` (modify stock): `name`, `email` unique safe, `password => 'password'` (hashed cast handles it), `role => Role::User`, `status => UserStatus::Active`; states:

```php
    public function developer(): static
    {
        return $this->state(fn () => ['role' => Role::Developer, 'status' => UserStatus::Pending])
            ->afterCreating(fn (User $u) => Developer::factory()->create(['user_id' => $u->id]));
    }

    public function verifiedDeveloper(): static
    {
        return $this->developer()->state(fn () => ['status' => UserStatus::Active]);
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => Role::Admin, 'status' => UserStatus::Active]);
    }
```

Other factories (all new files, `Database\Factories` namespace):
- `DeveloperFactory`: `studio_name => fake()->company()`, `portfolio => fake()->paragraph()`.
- `GameFactory`: `developer_id => DeveloperFactory::new()->forUser? no —` use `Developer::factory()` (create DeveloperFactory with `user_id => User::factory()->verifiedDeveloper()`... simpler: `developer_id` => closure creating a verified dev user + developer). Recommended shape:

```php
'developer_id' => Developer::factory(),
'name' => fake()->unique()->words(3, true),
'slug' => fn (array $a) => Str::slug($a['name']) . '-' . Str::lower(Str::random(4)),
'description' => fake()->paragraphs(2, true),
'price' => fake()->randomElement(['0.00', '25000.00', '75000.00', '150000.00']),
'genre' => fake()->randomElement(['Action', 'Adventure', 'RPG', 'Strategy', 'Simulation', 'Puzzle', 'Horror', 'Racing', 'Sports', 'Casual']),
'cover_path' => 'covers/fake.jpg',
'file_path' => 'installers/fake.zip',
'status' => GameStatus::Draft,
```

  with `DeveloperFactory` having `'user_id' => User::factory()->verifiedDeveloper()` — careful about recursion (`verifiedDeveloper` state creates a Developer). Break it: `DeveloperFactory` uses `'user_id' => User::factory()->state(['role' => Role::Developer, 'status' => UserStatus::Active])` directly (no `afterCreating`), and `UserFactory::developer()` state keeps its own `afterCreating`. States on `GameFactory`: `published()` → `['status' => GameStatus::Published]`, `free()` → `['price' => '0.00']`.
- `TagFactory`: `name => fake()->unique()->word()`.
- `IapFactory`: `game_id => Game::factory()`, `name`, `price => '10000.00'`, `type => IapType::Item`, `is_active => true`.
- `PromotionFactory`: `game_id`, `discount_percent => 20`, `starts_at => now()->subDay()`, `ends_at => now()->addWeek()`.
- `TransaksiFactory`: `order_id => fn () => Transaksi::generateOrderId()`, `user_id => User::factory()`, `game_id => Game::factory()->published()`, `iap_id => null`, `type => 'game'`, `total => '75000.00'`, `status => TransaksiStatus::Pending`; states `success()` and `failed()`. (No `iap()` state — an IAP transaction must share its `game_id` with the IAP's parent game, so tests build it explicitly: `Transaksi::factory()->create(['game_id' => $game->id, 'iap_id' => $iap->id, 'type' => 'iap'])`.)
- `PembayaranFactory`: `transaksi_id`, `method => PaymentMethod::EWallet`, `amount`, `reference_no => 'REF-' . strtoupper(Str::random(10))`, `payload => []`, `paid_at => now()`.
- `KomisiFactory`: `admin_id => User::factory()->admin()`, `percentage => '20.00'`, `effective_from => today()->subMonth()`.
- `KomisiTransaksiFactory`: `transaksi_id`, `komisi_id => Komisi::factory()`, `percentage => '20.00'`, `amount`, `developer_amount`, `payout_id => null`.
- `PayoutFactory`: `developer_id`, `admin_id => User::factory()->admin()`, `period => now()->format('Y-m')`, `gross`, `commission`, `net`, `status => 'processed'`.
- `LibraryFactory`: `user_id`, `game_id`, `transaksi_id => null`, `source => LibrarySource::Purchase`.
- `IapOwnershipFactory`: `user_id`, `iap_id`, `transaksi_id => Transaksi::factory()`.
- `WishlistFactory`: `user_id`, `game_id`.
- `PostFactory`: `user_id`, `body => fake()->sentence(12)`, `hashtags => ['gamelokal']`, `image_path => null`.
- `PostVoteFactory`: `post_id`, `user_id`, `value => 1`.
- `DownloadFactory`: `user_id`, `game_id`.

Every model used by a factory gets `use HasFactory;`.

- [ ] **Step 7: Run tests to verify they pass**

```bash
docker run --rm -v "$PWD/gpn":/app -w /app php:8.4-cli php artisan test --filter="SchemaTest|MoneyTest"
```

Expected: PASS. (Fix factory recursion / cast mistakes until green.)

- [ ] **Step 8: Commit**

```bash
git add -A && git commit -m "feat: domain schema, enums, models, factories (SRS 3.3 Kamus Data)"
```

---
### Task 4: Auth & roles (register, login, role middleware)

**Files:**
- Create: `gpn/app/Http/Controllers/Auth/AuthController.php`, `gpn/app/Http/Requests/Auth/{RegisterRequest,LoginRequest}.php`, `gpn/app/Http/Middleware/EnsureRole.php`, `gpn/resources/views/auth/{login,register}.blade.php`
- Modify: `gpn/routes/web.php`, `gpn/bootstrap/app.php`
- Test: `gpn/tests/Feature/AccountTest.php`

**Interfaces:**
- Consumes: `UserFactory` states, layouts, toasts (Tasks 2–3).
- Produces: routes `login`, `logout`, `register` (+ `home` placeholder, `profile.show` placeholder redirect), middleware alias `role:admin|developer`, session flash `account_created` (`'user'|'developer'`). Tasks 5+ rely on `route('home')`, `route('logout')`, the `role` middleware, and `auth()->user()` role helpers.

- [ ] **Step 1: Write the failing test**

`gpn/tests/Feature/AccountTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    private function validRegistration(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'account_type' => 'user',
            'terms' => 'on',
        ], $overrides);
    }

    public function test_register_user_activates_immediately_and_logs_in(): void // SRS 3.1.1.1
    {
        $response = $this->post('/register', $this->validRegistration());
        $user = User::where('email', 'budi@example.com')->firstOrFail();
        $this->assertSame(UserStatus::Active, $user->status);
        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('home'));
    }

    public function test_register_developer_is_pending_and_cannot_log_in(): void // SRS 3.1.1.1 + 3.1.1.2 alternate
    {
        $response = $this->post('/register', $this->validRegistration([
            'account_type' => 'developer',
            'studio_name' => 'Studio Nusantara',
        ]));
        $this->assertGuest();
        $user = User::where('email', 'budi@example.com')->firstOrFail();
        $this->assertSame(UserStatus::Pending, $user->status);
        $this->assertSame('Studio Nusantara', $user->developer->studio_name);
        $response->assertRedirect(route('login'));

        $login = $this->post('/login', ['email' => 'budi@example.com', 'password' => 'secret123']);
        $this->assertGuest();
        $login->assertSessionHas('error');
    }

    public function test_developer_registration_requires_studio_name(): void
    {
        $this->from('/register')->post('/register', $this->validRegistration(['account_type' => 'developer']))
            ->assertSessionHasErrors('studio_name');
    }

    public function test_duplicate_email_is_rejected_inline(): void // SRS 3.1.1.1 alternate
    {
        User::factory()->create(['email' => 'budi@example.com']);
        $this->from('/register')->post('/register', $this->validRegistration())
            ->assertSessionHasErrors('email');
    }

    public function test_login_wrong_password_shows_error(): void // SRS 3.1.1.2 exception
    {
        User::factory()->create(['email' => 'budi@example.com']);
        $this->from('/login')->post('/login', ['email' => 'budi@example.com', 'password' => 'wrong'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_role_redirects_after_login(): void // SRS F-02
    {
        $admin = User::factory()->admin()->create();
        $dev = User::factory()->verifiedDeveloper()->create();
        $user = User::factory()->create();

        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])->assertRedirect('/admin');
        auth()->logout();
        $this->post('/login', ['email' => $dev->email, 'password' => 'password'])->assertRedirect('/dev');
        auth()->logout();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('home'));
    }

    public function test_rejected_developer_login_shows_reason(): void
    {
        $dev = User::factory()->developer()->create(['status' => UserStatus::Rejected, 'rejection_reason' => 'Portfolio incomplete']);
        $this->from('/login')->post('/login', ['email' => $dev->email, 'password' => 'password'])
            ->assertSessionHas('error', fn ($msg) => str_contains($msg, 'Portfolio incomplete'));
    }

    public function test_role_middleware_blocks_wrong_role(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/admin')->assertForbidden();
        $this->actingAs($user)->get('/dev')->assertForbidden();
    }

    public function test_logout_clears_session(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/logout')->assertRedirect(route('home'));
        $this->assertGuest();
    }
}
```

Note: `/admin` and `/dev` need only exist as middleware-protected placeholders for this task (return 200 'ok'); Tasks 8/10 replace them.

- [ ] **Step 2: Run test to verify it fails**

```bash
docker run --rm -v "$PWD/gpn":/app -w /app php:8.4-cli php artisan test --filter=AccountTest
```

Expected: FAIL — routes do not exist.

- [ ] **Step 3: Requests, middleware, controller, routes**

`app/Http/Requests/Auth/RegisterRequest.php`:

```php
<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'account_type' => ['required', 'in:user,developer'],
            'studio_name' => ['required_if:account_type,developer', 'nullable', 'string', 'max:120'],
            'portfolio' => ['nullable', 'string', 'max:2000'],
            'terms' => ['accepted'],
        ];
    }
}
```

`LoginRequest`: `email` required email, `password` required string.

`app/Http/Middleware/EnsureRole.php` (plain class, no parent):

```php
<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();
        abort_unless($user && $user->role === Role::from($role), 403);

        if ($user->isDeveloper() && ! $user->isVerifiedDeveloper()) {
            abort(403, 'Your developer account is not verified.');
        }

        return $next($request);
    }
}
```

Register the alias in `bootstrap/app.php` → `->withMiddleware(function (Middleware $middleware) { $middleware->alias(['role' => \App\Http\Middleware\EnsureRole::class]); })`.

`app/Http/Controllers/Auth/AuthController.php`:

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Developer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'These credentials do not match our records.'])->onlyInput('email');
        }

        $user = $request->user();

        if ($user->isDeveloper() && $user->status === UserStatus::Pending) {
            Auth::logout();
            return back()->with('error', 'Your developer account is still awaiting admin verification.');
        }

        if ($user->isDeveloper() && $user->status === UserStatus::Rejected) {
            Auth::logout();
            return back()->with('error', 'Your developer account was rejected: ' . ($user->rejection_reason ?? 'no reason given'));
        }

        $request->session()->regenerate();

        return redirect()->intended(match (true) {
            $user->isAdmin() => '/admin',
            $user->isDeveloper() => '/dev',
            default => route('home'),
        });
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request): RedirectResponse // SRS 3.1.1.1 + 3-2.5
    {
        $data = $request->validated();
        $isDev = $data['account_type'] === 'developer';

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $isDev ? Role::Developer : Role::User,
            'status' => $isDev ? UserStatus::Pending : UserStatus::Active,
        ]);

        if ($isDev) {
            Developer::create([
                'user_id' => $user->id,
                'studio_name' => $data['studio_name'],
                'portfolio' => $data['portfolio'] ?? null,
            ]);

            return redirect()->route('login')->with('account_created', 'developer');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home')->with('account_created', 'user');
    }

    public function logout(): RedirectResponse
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('home');
    }
}
```

`routes/web.php` (this task's content — later tasks append their groups):

```php
<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => 'ok')->name('home'); // placeholder — Task 5 replaces

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('/dev', fn () => 'ok')->middleware(['auth', 'role:developer']); // placeholder — Task 8 replaces
Route::get('/admin', fn () => 'ok')->middleware(['auth', 'role:admin']);    // placeholder — Task 10 replaces
```

- [ ] **Step 4: Auth views**

`resources/views/auth/register.blade.php` — centered card on `bg-bg` (Figma Register `50:104` minus title bar): logo + "Create your account" heading (screen-title), account-type toggle (two radio cards: **Gamer** / **Developer** — JS toggles the studio fields), name/email/password/confirm inputs (`.input`), `studio_name` + `portfolio` block (hidden unless developer), terms checkbox with inline error, "Create Account" `.btn-primary` full width, footer link "Already have an account? Sign in" → `route('login')`. Every field renders `@error('field')<p class="field-error">{{ $message }}</p>@enderror` and `old()` values.

`resources/views/auth/login.blade.php` — same shell (Figma Login `6:1488`): "Welcome back", email/password, remember checkbox, "Sign in" `.btn-primary` with loading state (`onsubmit` swaps label to spinner via the Task-12 animation hook — plain submit is acceptable here), link "New here? Create an account". When `session('account_created')` is set, render an overlay div (`id="account-created"`) with a check icon + "Account created!" (+ for `'developer'`: "Your studio is awaiting admin verification.") that fades in then out over ~2.4s (CSS keyframes; respects `prefers-reduced-motion`; Task 12 owns the final polish of this overlay).

- [ ] **Step 5: Run tests to verify they pass**

```bash
docker run --rm -v "$PWD/gpn":/app -w /app php:8.4-cli php artisan test --filter=AccountTest
```

Expected: PASS (9 tests). `redirect()->intended()` needs `home` named route — placeholder provides it.

- [ ] **Step 6: Commit**

```bash
git add -A && git commit -m "feat: auth with roles, developer verification gate (SRS 3.1.1)"
```

---
### Task 5: Storefront — home, catalog, game detail, wishlist, download

**Files:**
- Create: `gpn/app/Http/Controllers/{HomeController,StoreController,WishlistController,DownloadController}.php`, `gpn/app/Http/Requests/StoreFilterRequest.php`, `gpn/resources/views/storefront/{home,game}.blade.php`, `gpn/resources/views/storefront/store.blade.php`, `gpn/resources/views/components/game-card.blade.php`, `gpn/resources/views/components/price.blade.php`
- Modify: `gpn/routes/web.php`
- Test: `gpn/tests/Feature/StorefrontTest.php`

**Interfaces:**
- Consumes: `<x-app-layout>`, `Game::published()`, `effectivePrice()`, `activePromotion()`, `ownedBy()`, `Money::format`.
- Produces: routes `home`, `store.index`, `store.show`, `wishlist.toggle` (`POST /wishlist/{game}`), `games.download` (`GET /games/{game:slug}/download`); `<x-game-card :game="$game" />`; `<x-price :game="$game" />`. Task 7's checkout buttons live in `storefront/game.blade.php` (it adds them; this task leaves a clearly marked `{{-- CHECKOUT-BUTTONS --}}` slot in the CTA area).

- [ ] **Step 1: Write the failing test**

`gpn/tests/Feature/StorefrontTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\GameStatus;
use App\Enums\LibrarySource;
use App\Models\{Download, Game, Library, Promotion, User, Wishlist};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_shows_only_published_games(): void
    {
        Game::factory()->published()->create(['name' => 'Pocong Runner']);
        Game::factory()->create(['name' => 'Draft Quest', 'status' => GameStatus::PendingReview]);
        $this->get('/')->assertOk()->assertSee('Pocong Runner')->assertDontSee('Draft Quest');
    }

    public function test_store_search_and_genre_filter(): void // SRS 3.1.2.1
    {
        Game::factory()->published()->create(['name' => 'Hantu Laut', 'genre' => 'Horror']);
        Game::factory()->published()->create(['name' => 'Balap Desa', 'genre' => 'Racing']);
        $this->get('/store?q=hantu')->assertSee('Hantu Laut')->assertDontSee('Balap Desa');
        $this->get('/store?genre=Racing')->assertSee('Balap Desa')->assertDontSee('Hantu Laut');
    }

    public function test_store_price_band_boundaries(): void
    {
        $free = Game::factory()->published()->free()->create();
        $cheap = Game::factory()->published()->create(['price' => '49999.00']);
        $mid = Game::factory()->published()->create(['price' => '150000.00']);
        $high = Game::factory()->published()->create(['price' => '150001.00']);

        $this->get('/store?price=free')->assertSee($free->name)->assertDontSee($cheap->name);
        $this->get('/store?price=under50')->assertSee($cheap->name)->assertDontSee($free->name)->assertDontSee($mid->name);
        $this->get('/store?price=50to150')->assertSee($mid->name)->assertDontSee($cheap->name)->assertDontSee($high->name);
        $this->get('/store?price=above150')->assertSee($high->name)->assertDontSee($mid->name);
    }

    public function test_store_empty_state_when_nothing_matches(): void // SRS 3.1.2.1 alternate
    {
        $this->get('/store?q=tidakada')->assertSee('No games found');
    }

    public function test_game_detail_hidden_when_not_published(): void // SRS 3.1.2.2
    {
        $draft = Game::factory()->create();
        $this->get("/store/{$draft->slug}")->assertNotFound();
    }

    public function test_game_detail_shows_promotion_strikethrough(): void
    {
        $game = Game::factory()->published()->create(['price' => '200000.00']);
        Promotion::factory()->create(['game_id' => $game->id, 'discount_percent' => 25]);
        $this->get("/store/{$game->slug}")
            ->assertSee('Rp 150.000')
            ->assertSee('Rp 200.000');
    }

    public function test_wishlist_toggle_requires_auth_and_toggles(): void
    {
        $game = Game::factory()->published()->create();
        $this->post("/wishlist/{$game->id}")->assertRedirect(route('login'));

        $user = User::factory()->create();
        $this->actingAs($user)->post("/wishlist/{$game->id}");
        $this->assertSame(1, Wishlist::count());
        $this->actingAs($user)->post("/wishlist/{$game->id}");
        $this->assertSame(0, Wishlist::count());
    }

    public function test_free_game_download_claims_library_and_logs(): void // SRS 3.1.2.3
    {
        Storage::fake('public');
        Storage::put('installers/game.zip', 'demo');
        $user = User::factory()->create();
        $game = Game::factory()->published()->free()->create(['file_path' => 'installers/game.zip']);

        $this->actingAs($user)->get("/games/{$game->slug}/download")->assertOk();
        $this->assertDatabaseHas('libraries', ['user_id' => $user->id, 'game_id' => $game->id, 'source' => LibrarySource::Free->value]);
        $this->assertSame(1, Download::count());
    }

    public function test_paid_game_download_redirects_to_purchase_when_unowned(): void // SRS 3.1.2.3 alternate
    {
        $user = User::factory()->create();
        $game = Game::factory()->published()->create(['price' => '75000.00']);
        $this->actingAs($user)->get("/games/{$game->slug}/download")
            ->assertRedirect("/store/{$game->slug}");
    }

    public function test_owned_game_download_streams_file(): void
    {
        Storage::fake('public');
        Storage::put('installers/game.zip', 'demo');
        $user = User::factory()->create();
        $game = Game::factory()->published()->create(['file_path' => 'installers/game.zip']);
        Library::factory()->create(['user_id' => $user->id, 'game_id' => $game->id]);

        $this->actingAs($user)->get("/games/{$game->slug}/download")
            ->assertOk()
            ->assertHeader('content-disposition');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
docker run --rm -v "$PWD/gpn":/app -w /app php:8.4-cli php artisan test --filter=StorefrontTest
```

Expected: FAIL.

- [ ] **Step 3: Controllers + routes**

`HomeController@index` (SRS §4.1, Figma Home `6:1427`):

```php
<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $hero = Game::published()
            ->whereHas('promotions', fn ($q) => $q->where('starts_at', '<=', now())->where('ends_at', '>=', now()))
            ->withCount('downloads')
            ->orderByDesc('downloads_count')
            ->first()
            ?? Game::published()->withCount('downloads')->orderByDesc('downloads_count')->first();

        $popular = Game::published()->withCount('downloads')->orderByDesc('downloads_count')->limit(10)->get();
        $latest = Game::published()->latest()->limit(10)->get();

        $showIntro = ! session()->has('intro_seen');
        session()->put('intro_seen', true);

        return view('storefront.home', compact('hero', 'popular', 'latest', 'showIntro'));
    }
}
```

`StoreController@index` — filters via `StoreFilterRequest` (`q` string|nullable, `genre` in fixed list|nullable, `tag` string|nullable, `price` in `free,under50,50to150,above150`|nullable, `sort` in `newest,price_asc,price_desc,name`|nullable). Query: `Game::published()` + `when()` clauses; price bands map exactly to spec §6 boundaries; paginate 12 `->withQueryString()`. Pass `$genres` (the fixed 10) and `$tags` (Tag::orderBy('name')->get()) to the view.

`StoreController@show(Game $game)` — **do NOT override `getRouteKeyName()`** (it would break id-bound routes like `/wishlist/{game}`); bind slugs explicitly per-route (`{game:slug}`) and ids elsewhere. Abort 404 unless `published()` or the viewer is the owning developer/admin; load `screenshots`, `iaps` (active), `tags`, `developer`; pass `inLibrary` (`Library::where('user_id', auth()->id())->where('game_id', $game->id)->exists()` — `ownedBy()` counts free games as owned, so it can't be used here) and `wishlisted`.

`WishlistController@toggle(Game $game)` (auth): `Wishlist::firstWhere` → delete + flash 'Removed from wishlist' or create + flash 'Added to wishlist'; `return back()`.

`DownloadController@show(Game $game)` (auth; bind `{game:slug}`): ownership = library row or `$game->isFree()`; else redirect `store.show` with `error` flash 'Purchase this game to download it.'; free+not-in-library → create Library row (source `free`, wrapped in try/catch for race — unique key violation = already claimed); log `Download::create`; `return Storage::disk('public')->download($game->file_path, $game->slug . '-installer.zip')`.

Routes (replace the `home` placeholder from Task 4):

```php
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/store', [StoreController::class, 'index'])->name('store.index');
Route::get('/store/{game:slug}', [StoreController::class, 'show'])->name('store.show');

Route::middleware('auth')->group(function () {
    Route::post('/wishlist/{game}', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
    Route::get('/games/{game:slug}/download', [DownloadController::class, 'show'])->name('games.download');
});
```

- [ ] **Step 4: Views**

`components/price.blade.php`:

```blade
@props(['game'])
@php $promo = $game->activePromotion(); @endphp
@if ($game->isFree())
    <span class="font-semibold text-success">Free</span>
@elseif ($promo)
    <span class="flex items-center gap-2">
        <span class="text-faint line-through">{{ \App\Support\Money::format($game->price) }}</span>
        <span class="font-semibold">{{ \App\Support\Money::format($game->effectivePrice()) }}</span>
        <span class="rounded bg-success/15 px-1.5 py-0.5 text-xs font-medium text-success">-{{ $promo->discount_percent }}%</span>
    </span>
@else
    <span class="font-semibold">{{ \App\Support\Money::format($game->price) }}</span>
@endif
```

`components/game-card.blade.php` — `card` wrapper, cover (`asset('storage/'.$game->cover_path)`, aspect 16:9, `hover:scale-[1.02]` transition), name (semibold), genre + tags muted line, `<x-price :game="$game" />`, whole card wrapped in `store.show` link.

`storefront/home.blade.php` — hero section (Figma Home): large 16:9 card with hero cover, name, short description, price + CTA link; "Popular" and "New Releases" rows of `<x-game-card>` in `grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-4`. Render the hero section only when `$hero` is non-null (a fresh install with zero games must still return 200). If `$showIntro`, render overlay `#intro-overlay` (logo pulse → wordmark fade, ~1.2s, CSS keyframes, `prefers-reduced-motion` hides instantly, click to dismiss, JS removes after `animationend`).

`storefront/store.blade.php` — Figma Store `6:1698`: left sidebar `w-[220px]` (search input, genre radio list, tags checklist→single-select link style, price band radios) as a GET form; main area: sort `<select name="sort" onchange="this.form.submit()">` + results grid; empty state "No games found" (SRS alternate flow); `{{ $games->links() }}`.

`storefront/game.blade.php` — Figma Game `6:1559`: breadcrumb back to store, cover hero w/ screenshots strip, title, developer studio name, tags pills, description, trailer link if present, price block, CTA region:

```blade
@if ($inLibrary || $game->isFree())
    <a href="{{ route('games.download', $game->slug) }}" class="btn-primary"><x-icon name="download" class="h-5 w-5" /> Download</a>
@else
    {{-- CHECKOUT-BUTTONS --}}
@endif
@auth
    <form method="POST" action="{{ route('wishlist.toggle', $game) }}">@csrf
        <button class="btn-ghost"><x-icon name="heart" class="h-5 w-5 {{ $wishlisted ? 'text-danger' : '' }}" /> {{ $wishlisted ? 'Wishlisted' : 'Wishlist' }}</button>
    </form>
@endauth
```

IAP list section (active IAPs with name, type, price) with a `{{-- IAP-BUY --}}` slot per item. Trailer: external link icon.

- [ ] **Step 5: Run tests to verify they pass**

```bash
docker run --rm -v "$PWD/gpn":/app -w /app php:8.4-cli php artisan test --filter=StorefrontTest
```

Expected: PASS (10 tests).

- [ ] **Step 6: Commit**

```bash
git add -A && git commit -m "feat: storefront home, catalog with filters, game detail, wishlist, downloads (SRS 3.1.2.1-3.1.2.3)"
```

---
### Task 6: Profile & transaction history

**Files:**
- Create: `gpn/app/Http/Controllers/{ProfileController,TransactionController}.php`, `gpn/app/Http/Requests/ProfileUpdateRequest.php`, `gpn/resources/views/storefront/profile.blade.php`, `gpn/resources/views/storefront/transactions/{index,show}.blade.php`
- Modify: `gpn/routes/web.php`
- Test: `gpn/tests/Feature/ProfileTest.php`, `gpn/tests/Feature/TransactionHistoryTest.php`

**Interfaces:**
- Consumes: `Library`, `Wishlist`, `IapOwnership`, `Transaksi` models, `<x-app-layout>`, `Money::format`.
- Produces: routes `profile.show`, `profile.update` (`PUT /profile`), `profile.showcase` (`POST /profile/showcase/{library}`), `transactions.index`, `transactions.show` (`/transactions/{transaksi:order_id}`). Task 7 links to `transactions.*` from the result page.

- [ ] **Step 1: Write the failing tests**

`gpn/tests/Feature/ProfileTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\{Game, Iap, IapOwnership, Library, User, Wishlist};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_requires_auth(): void
    {
        $this->get('/profile')->assertRedirect(route('login'));
    }

    public function test_profile_shows_stats_and_library(): void // SRS 4.1
    {
        $user = User::factory()->create();
        $game = Game::factory()->published()->create(['name' => 'Kuntilanak Mansion']);
        Library::factory()->create(['user_id' => $user->id, 'game_id' => $game->id]);
        Wishlist::factory()->create(['user_id' => $user->id]);
        IapOwnership::factory()->create(['user_id' => $user->id, 'iap_id' => Iap::factory()->for($game)]);

        $this->actingAs($user)->get('/profile')
            ->assertOk()
            ->assertSee('Kuntilanak Mansion')
            ->assertSeeInOrder(['Games owned', 'Wishlist', 'IAP items']);
    }

    public function test_showcase_toggle_controls_profile_main_section(): void // SRS 4.1
    {
        $user = User::factory()->create();
        $library = Library::factory()->create(['user_id' => $user->id, 'is_showcased' => true]);
        $this->actingAs($user)->post("/profile/showcase/{$library->id}");
        $this->assertFalse($library->fresh()->is_showcased);
    }

    public function test_user_cannot_toggle_anothers_library(): void
    {
        $library = Library::factory()->create();
        $this->actingAs(User::factory()->create())->post("/profile/showcase/{$library->id}")->assertForbidden();
    }

    public function test_profile_update_name_and_password(): void // SRS 3.1.1.3
    {
        $user = User::factory()->create(['name' => 'Old Name']);
        $this->actingAs($user)->put('/profile', ['name' => 'New Name'])
            ->assertRedirect(route('profile.show'));
        $this->assertSame('New Name', $user->fresh()->name);

        $this->actingAs($user)->put('/profile', [
            'name' => 'New Name',
            'current_password' => 'password',
            'password' => 'new-secret-1',
            'password_confirmation' => 'new-secret-1',
        ]);
        $this->assertTrue(auth()->attempt(['email' => $user->email, 'password' => 'new-secret-1']));
    }

    public function test_password_change_requires_current_password(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->from('/profile')->put('/profile', [
            'name' => $user->name,
            'password' => 'new-secret-1',
            'password_confirmation' => 'new-secret-1',
        ])->assertSessionHasErrors('current_password');
    }
}
```

`gpn/tests/Feature/TransactionHistoryTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\TransaksiStatus;
use App\Models\{Pembayaran, Transaksi, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_lists_own_transactions_newest_first(): void // SRS 3.1.3.4
    {
        $user = User::factory()->create();
        $old = Transaksi::factory()->create(['user_id' => $user->id, 'created_at' => now()->subDays(2)]);
        $new = Transaksi::factory()->create(['user_id' => $user->id, 'created_at' => now()]);
        Transaksi::factory()->create(); // someone else's

        $this->actingAs($user)->get('/transactions')
            ->assertOk()
            ->assertSeeInOrder([$new->order_id, $old->order_id])
            ->assertViewHas('transaksis', fn ($t) => $t->count() === 2);
    }

    public function test_empty_history_state(): void // SRS 3.1.3.4 alternate
    {
        $this->actingAs(User::factory()->create())->get('/transactions')->assertSee('No transactions yet');
    }

    public function test_detail_shows_payment_and_status(): void
    {
        $user = User::factory()->create();
        $trx = Transaksi::factory()->success()->create(['user_id' => $user->id]);
        Pembayaran::factory()->create(['transaksi_id' => $trx->id, 'reference_no' => 'REF-ABC123']);

        $this->actingAs($user)->get("/transactions/{$trx->order_id}")
            ->assertOk()
            ->assertSee('REF-ABC123')
            ->assertSee('Success');
    }

    public function test_detail_forbidden_for_other_users(): void
    {
        $trx = Transaksi::factory()->create();
        $this->actingAs(User::factory()->create())->get("/transactions/{$trx->order_id}")->assertForbidden();
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
docker run --rm -v "$PWD/gpn":/app -w /app php:8.4-cli php artisan test --filter="ProfileTest|TransactionHistoryTest"
```

Expected: FAIL.

- [ ] **Step 3: Controllers, request, routes**

`ProfileUpdateRequest` rules: `name` required max:100; `avatar` nullable image max:2048; `password` nullable confirmed min:8; `current_password` required_with:password + `current_password` rule (Laravel's built-in `current_password` validation rule).

`ProfileController`:

```php
public function show(): View // SRS 4.1
{
    $user = auth()->user()->load(['libraries.game', 'wishlists.game', 'iapOwnerships.iap']);

    return view('storefront.profile', [
        'user' => $user,
        'showcased' => $user->libraries->where('is_showcased', true),
    ]);
}

public function update(ProfileUpdateRequest $request): RedirectResponse // SRS 3.1.1.3
{
    $user = $request->user();
    $data = $request->safe()->only(['name']);

    if ($request->hasFile('avatar')) {
        $data['avatar_path'] = $request->file('avatar')->store('avatars', 'public');
    }

    if ($request->filled('password')) {
        $data['password'] = $request->input('password');
    }

    $user->update($data);

    return redirect()->route('profile.show')->with('success', 'Profile updated.');
}

public function showcase(Library $library): RedirectResponse // SRS 4.1
{
    abort_unless($library->user_id === auth()->id(), 403);
    $library->update(['is_showcased' => ! $library->is_showcased]);

    return back()->with('success', 'Showcase updated.');
}
```

`TransactionController@index`: `return view('storefront.transactions.index', ['transaksis' => auth()->user()->transaksis()->with('game', 'iap')->latest()->paginate(15)])` (the view variable MUST be named `transaksis` — the test asserts on it). `@show(Transaksi $transaksi)` (bind `{transaksi:order_id}`): `abort_unless($transaksi->user_id === auth()->id(), 403)`; load `pembayaran`, `game`, `iap`.

Routes (inside `auth` group):

```php
Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
Route::post('/profile/showcase/{library}', [ProfileController::class, 'showcase'])->name('profile.showcase');
Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
Route::get('/transactions/{transaksi:order_id}', [TransactionController::class, 'show'])->name('transactions.show');
```

- [ ] **Step 4: Views**

`storefront/profile.blade.php` (Figma Profile `6:1890`): header block (avatar circle with initial or uploaded avatar, name, role badge, "Edit profile" toggle button revealing the edit form via `<details>` or vanilla JS), stat cards (Games owned / Wishlist / IAP items), "Showcase" grid of showcased library games (each card with a showcase toggle form), "Your library" full grid with per-game toggle, wishlist row, edit form (name, avatar file, password + confirmation + current password) posting `PUT /profile`.

`storefront/transactions/index.blade.php` — table (date, order id, item name, nominal via `Money::format`, status pill colored: success green/pending amber/failed+expired red) linking to detail; empty state "No transactions yet".

`storefront/transactions/show.blade.php` — detail card: order id, game/IAP name, total, status, payment method label, reference no, paid_at, timeline (Created → Paid/Failed/Expired), download button if settled game purchase.

- [ ] **Step 5: Run tests to verify they pass**

```bash
docker run --rm -v "$PWD/gpn":/app -w /app php:8.4-cli php artisan test --filter="ProfileTest|TransactionHistoryTest"
```

Expected: PASS (10 tests).

- [ ] **Step 6: Commit**

```bash
git add -A && git commit -m "feat: profile with library showcase, transaction history (SRS 4.1, 3.1.1.3, 3.1.3.4)"
```

---
### Task 7: Payment core & checkout (simulated gateway, signed webhook, settlement)

**Files:**
- Create: `gpn/app/Services/Payment/{Signature,SimulatedGateway}.php`, `gpn/app/Services/{SettlementService,CommissionService}.php`, `gpn/app/Exceptions/Payment/{PaymentNotConfiguredException,InvalidSignatureException,AmountMismatchException}.php`, `gpn/app/Http/Controllers/{PaymentWebhookController,GatewayController,CheckoutController}.php`, `gpn/app/Console/Commands/ExpireTransactions.php`, `gpn/resources/views/checkout/{summary,result}.blade.php`, `gpn/resources/views/gateway/show.blade.php`
- Modify: `gpn/routes/web.php`, `gpn/routes/console.php`, `gpn/resources/views/storefront/game.blade.php` (fill `CHECKOUT-BUTTONS` + `IAP-BUY` slots), `gpn/bootstrap/app.php` (CSRF exemption)
- Test: `gpn/tests/Feature/PaymentWebhookTest.php`, `gpn/tests/Feature/CheckoutTest.php`

**Interfaces:**
- Consumes: models + enums (Task 3), `config('services.gpn_payment.server_key')`, `config('gpn.*')` (Task 1), game detail CTA slots (Task 5), `transactions.show` route (Task 6).
- Produces: `Signature::make(string $orderId, string $statusCode, string $grossAmount, string $key): string`, `Signature::gross(string $total): string`; `SimulatedGateway::payload(Transaksi $trx, string $transactionStatus, string $method): array`; `SettlementService::handleNotification(array $payload): Transaksi` (throws `PaymentNotConfiguredException`, `InvalidSignatureException`, `AmountMismatchException`, `ModelNotFoundException`); `CommissionService::snapshot(Transaksi $trx): KomisiTransaksi`, `CommissionService::percentageFor(CarbonInterface $date): string`; routes `checkout.store|summary|pay|cancel|result`, `gateway.show|complete`, `webhooks.payment`; command `transactions:expire`. Tasks 8–10 consume `komisi_transaksi` rows + `KomisiTransaksi.payout_id`.

- [ ] **Step 1: Write the failing tests**

`gpn/tests/Feature/PaymentWebhookTest.php` (SRS 3.1.3.3 + activity 3-2.4 + Review Focus 1–3):

```php
<?php

namespace Tests\Feature;

use App\Enums\{TransaksiStatus, LibrarySource};
use App\Models\{Iap, IapOwnership, Komisi, KomisiTransaksi, Library, Pembayaran, Transaksi, User};
use App\Services\Payment\Signature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function signedPayload(Transaksi $trx, string $status = 'settlement', ?string $amount = null): array
    {
        $gross = $amount ?? Signature::gross($trx->total);
        return [
            'order_id' => $trx->order_id,
            'status_code' => '200',
            'transaction_status' => $status,
            'gross_amount' => $gross,
            'payment_type' => 'e_wallet',
            'signature_key' => Signature::make($trx->order_id, '200', $gross, config('services.gpn_payment.server_key')),
        ];
    }

    public function test_valid_settlement_grants_library_and_snapshots_commission(): void // SRS 3.1.3.3
    {
        Komisi::factory()->create(['percentage' => '20.00', 'effective_from' => today()->subMonth()]);
        $trx = Transaksi::factory()->create(['total' => '100000.00']);

        $this->postJson('/webhooks/payment', $this->signedPayload($trx))->assertOk();

        $trx->refresh();
        $this->assertSame(TransaksiStatus::Success, $trx->status);
        $this->assertDatabaseHas('pembayarans', ['transaksi_id' => $trx->id, 'method' => 'e_wallet']);
        $this->assertDatabaseHas('libraries', ['user_id' => $trx->user_id, 'game_id' => $trx->game_id, 'source' => LibrarySource::Purchase->value]);
        $row = KomisiTransaksi::where('transaksi_id', $trx->id)->sole();
        $this->assertSame('20000.00', $row->amount);
        $this->assertSame('80000.00', $row->developer_amount);
        $this->assertSame('20.00', $row->percentage);
    }

    public function test_forged_signature_is_rejected(): void // SRS 3-2.4 invalid signature
    {
        $trx = Transaksi::factory()->create();
        $payload = $this->signedPayload($trx);
        $payload['signature_key'] = 'forged';
        $this->postJson('/webhooks/payment', $payload)->assertForbidden();
        $this->assertSame(TransaksiStatus::Pending, $trx->fresh()->status);
    }

    public function test_missing_server_key_fails_closed(): void
    {
        config()->set('services.gpn_payment.server_key', null);
        $trx = Transaksi::factory()->create();
        $this->postJson('/webhooks/payment', $this->signedPayload($trx))->assertStatus(503);
    }

    public function test_amount_mismatch_is_rejected(): void // Review Focus 1
    {
        $trx = Transaksi::factory()->create(['total' => '100000.00']);
        $this->postJson('/webhooks/payment', $this->signedPayload($trx, 'settlement', '1000.00'))->assertStatus(422);
        $this->assertSame(TransaksiStatus::Pending, $trx->fresh()->status);
    }

    public function test_replay_is_idempotent(): void
    {
        $trx = Transaksi::factory()->create();
        $payload = $this->signedPayload($trx);
        $this->postJson('/webhooks/payment', $payload)->assertOk();
        $this->postJson('/webhooks/payment', $payload)->assertOk();
        $this->assertSame(1, Library::count());
    }

    public function test_double_settlement_is_idempotent(): void // Review Focus 3
    {
        $trx = Transaksi::factory()->create();
        $payload = $this->signedPayload($trx);
        $this->postJson('/webhooks/payment', $payload)->assertOk();
        $payload['payment_type'] = 'credit_card'; // a second, different delivery
        $payload['signature_key'] = Signature::make($trx->order_id, '200', $payload['gross_amount'], config('services.gpn_payment.server_key'));
        $this->postJson('/webhooks/payment', $payload)->assertOk();
        $this->assertSame(1, Library::count());
        $this->assertSame(1, KomisiTransaksi::count());
        $this->assertSame(1, Pembayaran::count());
    }

    public function test_status_mappings(): void
    {
        $pending = Transaksi::factory()->create();
        $this->postJson('/webhooks/payment', $this->signedPayload($pending, 'pending'))->assertOk();
        $this->assertSame(TransaksiStatus::Pending, $pending->fresh()->status);

        $denied = Transaksi::factory()->create();
        $this->postJson('/webhooks/payment', $this->signedPayload($denied, 'deny'))->assertOk();
        $this->assertSame(TransaksiStatus::Failed, $denied->fresh()->status);

        $expired = Transaksi::factory()->create();
        $this->postJson('/webhooks/payment', $this->signedPayload($expired, 'expire'))->assertOk();
        $this->assertSame(TransaksiStatus::Expired, $expired->fresh()->status);
    }

    public function test_unknown_order_is_404(): void
    {
        $payload = $this->signedPayload(Transaksi::factory()->create());
        $payload['order_id'] = 'GPN-20990101-XXXXXX';
        $payload['signature_key'] = Signature::make($payload['order_id'], '200', $payload['gross_amount'], config('services.gpn_payment.server_key'));
        $this->postJson('/webhooks/payment', $payload)->assertNotFound();
    }

    public function test_expire_command_marks_stale_pending(): void // SRS 3.1.3.3 kedaluwarsa
    {
        $stale = Transaksi::factory()->create(['created_at' => now()->subHours(25), 'updated_at' => now()->subHours(25)]);
        $fresh = Transaksi::factory()->create();
        $this->artisan('transactions:expire')->assertSuccessful();
        $this->assertSame(TransaksiStatus::Expired, $stale->fresh()->status);
        $this->assertSame(TransaksiStatus::Pending, $fresh->fresh()->status);
    }

    public function test_iap_settlement_grants_iap_not_base_game(): void // legacy bug regression
    {
        $game = \App\Models\Game::factory()->published()->create();
        $iap = Iap::factory()->for($game)->create();
        $trx = Transaksi::factory()->create(['game_id' => $game->id, 'iap_id' => $iap->id, 'type' => 'iap']);
        $this->postJson('/webhooks/payment', $this->signedPayload($trx))->assertOk();
        $this->assertSame(1, IapOwnership::where('user_id', $trx->user_id)->where('iap_id', $iap->id)->count());
        $this->assertSame(0, Library::count());
    }
}
```

`gpn/tests/Feature/CheckoutTest.php` (SRS 3.1.3.1/3.1.3.2 + Review Focus 2):

```php
<?php

namespace Tests\Feature;

use App\Enums\{GameStatus, TransaksiStatus};
use App\Models\{Game, Iap, Library, Transaksi, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_buy_creates_pending_transaction_and_shows_summary(): void // SRS 3.1.3.1
    {
        $user = User::factory()->create();
        $game = Game::factory()->published()->create(['price' => '75000.00']);
        $response = $this->actingAs($user)->post("/checkout/game/{$game->id}");
        $trx = Transaksi::sole();
        $this->assertSame(TransaksiStatus::Pending, $trx->status);
        $this->assertSame('75000.00', $trx->total);
        $response->assertRedirect(route('checkout.summary', $trx->order_id));
        $this->get(route('checkout.summary', $trx->order_id))->assertOk()->assertSee('Rp 75.000');
    }

    public function test_promotion_price_is_charged(): void
    {
        $user = User::factory()->create();
        $game = Game::factory()->published()->create(['price' => '200000.00']);
        \App\Models\Promotion::factory()->create(['game_id' => $game->id, 'discount_percent' => 25]);
        $this->actingAs($user)->post("/checkout/game/{$game->id}");
        $this->assertSame('150000.00', Transaksi::sole()->total);
    }

    public function test_double_purchase_is_blocked(): void
    {
        $user = User::factory()->create();
        $game = Game::factory()->published()->create();
        Library::factory()->create(['user_id' => $user->id, 'game_id' => $game->id]);
        $this->actingAs($user)->post("/checkout/game/{$game->id}")->assertRedirect(route('store.show', $game->slug));
        $this->assertSame(0, Transaksi::count());
    }

    public function test_free_game_checkout_redirects_to_download(): void
    {
        $user = User::factory()->create();
        $game = Game::factory()->published()->free()->create();
        $this->actingAs($user)->post("/checkout/game/{$game->id}")->assertRedirect(route('games.download', $game->slug));
        $this->assertSame(0, Transaksi::count());
    }

    public function test_iap_requires_base_game_ownership(): void
    {
        $user = User::factory()->create();
        $game = Game::factory()->published()->create(['price' => '50000.00']);
        $iap = Iap::factory()->for($game)->create();
        $this->actingAs($user)->post("/checkout/iap/{$iap->id}")->assertRedirect(route('store.show', $game->slug));
        $this->assertSame(0, Transaksi::count());

        Library::factory()->create(['user_id' => $user->id, 'game_id' => $game->id]);
        $this->actingAs($user)->post("/checkout/iap/{$iap->id}");
        $this->assertSame(1, Transaksi::where('type', 'iap')->count());
    }

    public function test_pay_requires_agreement_then_redirects_to_gateway(): void
    {
        $user = User::factory()->create();
        $trx = Transaksi::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user)->post("/checkout/{$trx->order_id}/pay")->assertSessionHasErrors('agreement');
        $this->actingAs($user)->post("/checkout/{$trx->order_id}/pay", ['agreement' => 'on'])
            ->assertRedirect(route('gateway.show', $trx->order_id));
    }

    public function test_simulator_success_settles_and_result_page_shows_success(): void
    {
        $user = User::factory()->create();
        $trx = Transaksi::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user)->post("/gateway/{$trx->order_id}/complete", ['method' => 'e_wallet', 'outcome' => 'settlement'])
            ->assertRedirect(route('checkout.result', $trx->order_id));
        $this->assertSame(TransaksiStatus::Success, $trx->fresh()->status);
        $this->get(route('checkout.result', $trx->order_id))->assertSee('Payment successful');
    }

    public function test_simulator_failure_shows_failed_result(): void
    {
        $user = User::factory()->create();
        $trx = Transaksi::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user)->post("/gateway/{$trx->order_id}/complete", ['method' => 'virtual_account', 'outcome' => 'deny']);
        $this->assertSame(TransaksiStatus::Failed, $trx->fresh()->status);
        $this->get(route('checkout.result', $trx->order_id))->assertSee('Payment failed');
    }

    public function test_retry_after_failed_payment_creates_new_transaction(): void // Review Focus 2
    {
        $user = User::factory()->create();
        $game = Game::factory()->published()->create();
        Transaksi::factory()->failed()->create(['user_id' => $user->id, 'game_id' => $game->id]);
        $this->actingAs($user)->post("/checkout/game/{$game->id}");
        $this->assertSame(1, Transaksi::where('status', TransaksiStatus::Pending)->count());
    }

    public function test_cancel_keeps_transaction_pending(): void // SRS 3.1.3.1 alternate
    {
        $user = User::factory()->create();
        $trx = Transaksi::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user)->post("/checkout/{$trx->order_id}/cancel")
            ->assertRedirect(route('store.show', $trx->game->slug));
        $this->assertSame(TransaksiStatus::Pending, $trx->fresh()->status);
    }

    public function test_users_cannot_touch_each_others_checkout(): void
    {
        $trx = Transaksi::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($other)->get("/checkout/{$trx->order_id}")->assertForbidden();
        $this->actingAs($other)->post("/checkout/{$trx->order_id}/pay", ['agreement' => 'on'])->assertForbidden();
        $this->actingAs($other)->post("/gateway/{$trx->order_id}/complete", ['method' => 'e_wallet', 'outcome' => 'settlement'])->assertForbidden();
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
docker run --rm -v "$PWD/gpn":/app -w /app php:8.4-cli php artisan test --filter="PaymentWebhookTest|CheckoutTest"
```

Expected: FAIL.

- [ ] **Step 3: Payment services**

`app/Services/Payment/Signature.php`:

```php
<?php

namespace App\Services\Payment;

class Signature
{
    public static function gross(string $total): string
    {
        return number_format((float) $total, 2, '.', '');
    }

    public static function make(string $orderId, string $statusCode, string $grossAmount, string $key): string
    {
        return hash('sha512', $orderId . $statusCode . $grossAmount . $key);
    }

    public static function isValid(array $payload, string $key): bool
    {
        $expected = static::make(
            (string) ($payload['order_id'] ?? ''),
            (string) ($payload['status_code'] ?? ''),
            (string) ($payload['gross_amount'] ?? ''),
            $key
        );

        return hash_equals($expected, (string) ($payload['signature_key'] ?? ''));
    }
}
```

`app/Services/Payment/SimulatedGateway.php`:

```php
<?php

namespace App\Services\Payment;

use App\Models\Transaksi;

class SimulatedGateway
{
    /** Build a Midtrans-shaped, properly signed notification (SRS activity 3-2.2/3-2.4). */
    public static function payload(Transaksi $trx, string $transactionStatus, string $method): array
    {
        $statusCode = $transactionStatus === 'deny' ? '202' : '200';
        $gross = Signature::gross($trx->total);

        return [
            'order_id' => $trx->order_id,
            'status_code' => $statusCode,
            'transaction_status' => $transactionStatus, // settlement|pending|deny|expire
            'gross_amount' => $gross,
            'payment_type' => $method,                  // e_wallet|virtual_account|credit_card
            'transaction_time' => now()->toIso8601String(),
            'signature_key' => Signature::make($trx->order_id, $statusCode, $gross, (string) config('services.gpn_payment.server_key')),
        ];
    }
}
```

Exceptions — three tiny classes in `app/Exceptions/Payment/` extending `RuntimeException`, empty bodies.

`app/Services/CommissionService.php`:

```php
<?php

namespace App\Services;

use App\Models\Komisi;
use App\Models\KomisiTransaksi;
use App\Models\Transaksi;
use Carbon\CarbonInterface;

class CommissionService
{
    public function percentageFor(CarbonInterface $date): string
    {
        // Only rows ALREADY in effect count — a future-dated schedule must never apply early.
        $row = Komisi::whereDate('effective_from', '<=', $date->toDateString())
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();

        return $row?->percentage ?? config('gpn.default_commission');
    }

    public function snapshot(Transaksi $trx): KomisiTransaksi
    {
        $percentage = $this->percentageFor(now());
        $komisi = Komisi::whereDate('effective_from', '<=', today())
            ->orderByDesc('effective_from')->orderByDesc('id')->first()
            ?? Komisi::create(['admin_id' => \App\Models\User::where('role', 'admin')->value('id') ?? 1, 'percentage' => $percentage, 'effective_from' => today()]);
        $amount = bcmul($trx->total, bcdiv($percentage, '100', 4), 2);

        return KomisiTransaksi::create([
            'transaksi_id' => $trx->id,
            'komisi_id' => $komisi->id,
            'percentage' => $percentage,
            'amount' => $amount,
            'developer_amount' => bcsub($trx->total, $amount, 2),
        ]);
    }
}
```

`app/Services/SettlementService.php` (SRS 3-2.4 + NF-05):

```php
<?php

namespace App\Services;

use App\Enums\LibrarySource;
use App\Enums\TransaksiStatus;
use App\Exceptions\Payment\AmountMismatchException;
use App\Exceptions\Payment\InvalidSignatureException;
use App\Exceptions\Payment\PaymentNotConfiguredException;
use App\Models\IapOwnership;
use App\Models\Library;
use App\Models\Pembayaran;
use App\Models\Transaksi;
use App\Services\Payment\Signature;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

class SettlementService
{
    public function __construct(
        private CommissionService $commission,
        private LoggerInterface $log,
    ) {}

    public function handleNotification(array $payload): Transaksi
    {
        $key = config('services.gpn_payment.server_key');
        if (! $key) {
            throw new PaymentNotConfiguredException('Payment server key is not configured.');
        }
        if (! Signature::isValid($payload, $key)) {
            $this->log->warning('Payment webhook rejected: invalid signature', ['order_id' => $payload['order_id'] ?? null]);
            throw new InvalidSignatureException;
        }

        /** @var Transaksi $trx */
        $trx = Transaksi::where('order_id', $payload['order_id'])->firstOrFail();

        if (Signature::gross($trx->total) !== (string) $payload['gross_amount']) {
            throw new AmountMismatchException;
        }

        if ($trx->isFinal()) {
            return $trx; // idempotent replay
        }

        return match ($payload['transaction_status']) {
            'settlement', 'capture' => $this->settle($trx, $payload),
            'pending' => $trx,
            'expire' => $this->mark($trx, TransaksiStatus::Expired),
            default => $this->mark($trx, TransaksiStatus::Failed), // deny|cancel
        };
    }

    private function settle(Transaksi $trx, array $payload): Transaksi
    {
        return DB::transaction(function () use ($trx, $payload) {
            // NB: lock the row by key — $model->lockForUpdate() issues NO SQL (discarded builder).
            $trx = Transaksi::whereKey($trx->getKey())->lockForUpdate()->first();
            if ($trx->isFinal()) {
                return $trx;
            }

            $trx->update(['status' => TransaksiStatus::Success]);
            Pembayaran::create([
                'transaksi_id' => $trx->id,
                'method' => $payload['payment_type'],
                'amount' => $trx->total,
                'reference_no' => 'REF-' . strtoupper(Str::random(10)),
                'payload' => $payload,
                'paid_at' => now(),
            ]);

            if ($trx->type === 'iap' && $trx->iap_id) {
                IapOwnership::firstOrCreate(
                    ['user_id' => $trx->user_id, 'iap_id' => $trx->iap_id],
                    ['transaksi_id' => $trx->id]
                );
            } else {
                Library::firstOrCreate(
                    ['user_id' => $trx->user_id, 'game_id' => $trx->game_id],
                    ['transaksi_id' => $trx->id, 'source' => LibrarySource::Purchase]
                );
            }

            $this->commission->snapshot($trx);

            return $trx->refresh();
        });
    }

    private function mark(Transaksi $trx, TransaksiStatus $status): Transaksi
    {
        // Conditional: a stale deny/expire delivery must never overwrite a settled terminal state.
        Transaksi::whereKey($trx->getKey())
            ->where('status', TransaksiStatus::Pending->value)
            ->update(['status' => $status->value]);

        return $trx->refresh();
    }
}
```

- [ ] **Step 4: Controllers, command, routes**

`PaymentWebhookController`:

```php
<?php

namespace App\Http\Controllers;

use App\Exceptions\Payment\AmountMismatchException;
use App\Exceptions\Payment\InvalidSignatureException;
use App\Exceptions\Payment\PaymentNotConfiguredException;
use App\Services\SettlementService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    public function handle(Request $request, SettlementService $settlement): JsonResponse
    {
        try {
            $trx = $settlement->handleNotification($request->all());
        } catch (PaymentNotConfiguredException $e) {
            return response()->json(['message' => $e->getMessage()], 503);
        } catch (InvalidSignatureException) {
            return response()->json(['message' => 'Invalid signature'], 403);
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Unknown order'], 404);
        } catch (AmountMismatchException) {
            return response()->json(['message' => 'Amount mismatch'], 422);
        }

        return response()->json(['message' => 'OK', 'status' => $trx->status->value]);
    }
}
```

`GatewayController` (SRS 3-2.2; simulator labeled "SIMULATED PAYMENT"):

```php
<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use App\Services\Payment\SimulatedGateway;
use App\Services\SettlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GatewayController extends Controller
{
    public function show(Transaksi $transaksi): View
    {
        $this->authorizeOwner($transaksi);

        return view('gateway.show', ['trx' => $transaksi->load('game', 'iap')]);
    }

    public function complete(Request $request, Transaksi $transaksi, SettlementService $settlement): RedirectResponse
    {
        $this->authorizeOwner($transaksi);
        $data = $request->validate([
            'method' => ['required', 'in:e_wallet,virtual_account,credit_card'],
            'outcome' => ['required', 'in:settlement,deny,expire'],
        ]);

        if (! $transaksi->isFinal()) {
            $settlement->handleNotification(SimulatedGateway::payload($transaksi, $data['outcome'], $data['method']));
        }

        return redirect()->route('checkout.result', $transaksi->order_id);
    }

    private function authorizeOwner(Transaksi $transaksi): void
    {
        abort_unless($transaksi->user_id === auth()->id(), 403);
    }
}
```

Route model binding note: `{transaksi:order_id}` on gateway + checkout routes.

`CheckoutController`:

```php
<?php

namespace App\Http\Controllers;

use App\Enums\TransaksiStatus;
use App\Models\Game;
use App\Models\Iap;
use App\Models\Transaksi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function store(string $type, int $id): RedirectResponse // SRS 3.1.3.1/3.1.3.2
    {
        $user = auth()->user();
        abort_unless(in_array($type, ['game', 'iap']), 404);

        if ($type === 'game') {
            $game = Game::published()->findOrFail($id);
            if ($game->isFree()) {
                return redirect()->route('games.download', $game->slug);
            }
            // NB: check the Library table directly — Game::ownedBy() counts free games as owned.
            if (\App\Models\Library::where('user_id', $user->id)->where('game_id', $game->id)->exists()) {
                return redirect()->route('store.show', $game->slug)->with('error', 'You already own this game.');
            }
            $iap = null;
            $total = $game->effectivePrice();
        } else {
            $iap = Iap::where('is_active', true)->findOrFail($id);
            $game = $iap->game;
            $ownsBase = \App\Models\Library::where('user_id', $user->id)->where('game_id', $game->id)->exists();
            if (! $ownsBase && ! $game->isFree()) {
                return redirect()->route('store.show', $game->slug)->with('error', 'You need the base game before buying add-ons.');
            }
            if (\App\Models\IapOwnership::where('user_id', $user->id)->where('iap_id', $iap->id)->exists()) {
                return redirect()->route('store.show', $game->slug)->with('error', 'You already own this item.');
            }
            $total = $iap->price;
        }

        $trx = Transaksi::create([
            'order_id' => Transaksi::generateOrderId(),
            'user_id' => $user->id,
            'game_id' => $game->id,
            'iap_id' => $iap?->id,
            'type' => $type,
            'total' => $total,
            'status' => TransaksiStatus::Pending,
        ]);

        return redirect()->route('checkout.summary', $trx->order_id);
    }

    public function summary(Transaksi $transaksi): View
    {
        abort_unless($transaksi->user_id === auth()->id(), 403);

        return view('checkout.summary', ['trx' => $transaksi->load('game.developer', 'iap')]);
    }

    public function pay(Request $request, Transaksi $transaksi): RedirectResponse
    {
        abort_unless($transaksi->user_id === auth()->id(), 403);
        $request->validate(['agreement' => ['accepted']]);

        return redirect()->route('gateway.show', $transaksi->order_id);
    }

    public function cancel(Transaksi $transaksi): RedirectResponse
    {
        abort_unless($transaksi->user_id === auth()->id(), 403);

        return redirect()->route('store.show', $transaksi->game->slug)->with('success', 'Order cancelled. You can buy it anytime.');
    }

    public function result(Transaksi $transaksi): View
    {
        abort_unless($transaksi->user_id === auth()->id(), 403);

        return view('checkout.result', ['trx' => $transaksi->load('game', 'iap', 'pembayaran')]);
    }
}
```

`app/Console/Commands/ExpireTransactions.php` — signature `transactions:expire`; body: `Transaksi::where('status', TransaksiStatus::Pending->value)->where('created_at', '<', now()->subHours(config('gpn.transaction_ttl_hours')))->update(['status' => TransaksiStatus::Expired->value])`, print count. Schedule in `routes/console.php`: `Schedule::command('transactions:expire')->daily();`

Routes (auth group):

```php
Route::post('/checkout/{type}/{id}', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/checkout/{transaksi:order_id}', [CheckoutController::class, 'summary'])->name('checkout.summary');
Route::post('/checkout/{transaksi:order_id}/pay', [CheckoutController::class, 'pay'])->name('checkout.pay');
Route::post('/checkout/{transaksi:order_id}/cancel', [CheckoutController::class, 'cancel'])->name('checkout.cancel');
Route::get('/checkout/{transaksi:order_id}/result', [CheckoutController::class, 'result'])->name('checkout.result');
Route::get('/gateway/{transaksi:order_id}', [GatewayController::class, 'show'])->name('gateway.show');
Route::post('/gateway/{transaksi:order_id}/complete', [GatewayController::class, 'complete'])->name('gateway.complete');
```

Public: `Route::post('/webhooks/payment', [PaymentWebhookController::class, 'handle'])->name('webhooks.payment');` — and in `bootstrap/app.php` add to `withMiddleware`: `$middleware->validateCsrfTokens(except: ['webhooks/payment']);`

- [ ] **Step 5: Views**

`checkout/summary.blade.php` — order summary card (cover thumb, game/IAP name, studio, original price struck through if promo, total), terms-of-sale checkbox (`@error('agreement')` inline), Pay `.btn-primary` (posts `checkout.pay`), Cancel `.btn-ghost` (posts `checkout.cancel`).

`gateway/show.blade.php` — standalone minimal page (no app header): centered card with "SIMULATED PAYMENT — GPlayNation Dev Gateway" badge (`bg-elevated` pill, amber text), order id, amount, three method radio cards (E-Wallet / Virtual Account / Credit Card with icons), then three outcome buttons: **Pay (success)** green, **Fail** red ghost, **Expire** ghost — all submit `gateway.complete` with hidden `method` + `outcome`. Small print: "No real money moves. This drives the app's signed webhook pipeline."

`checkout/result.blade.php` — status hero: success (check icon, "Payment successful", library/download link + `transactions.show` link), failed ("Payment failed — try again", link back to game), expired ("Payment expired", link to game), pending ("Awaiting payment").

Fill the `storefront/game.blade.php` slots: `CHECKOUT-BUTTONS` → `<form method="POST" action="{{ route('checkout.store', ['game', $game->id]) }}">@csrf<button class="btn-primary">Buy now</button></form>`; `IAP-BUY` per item → same with `['iap', $iap->id]`, `.btn-ghost` "Buy".

- [ ] **Step 6: Run tests to verify they pass**

```bash
docker run --rm -v "$PWD/gpn":/app -w /app php:8.4-cli php artisan test --filter="PaymentWebhookTest|CheckoutTest"
```

Expected: PASS (21 tests).

- [ ] **Step 7: Commit**

```bash
git add -A && git commit -m "feat: checkout with simulated gateway, signed webhook settlement, commission snapshots (SRS 3.1.3, 3-2.2, 3-2.4)"
```

---
### Task 8: Developer portal — dashboard, games, IAP, settings, payouts

**Files:**
- Create: `gpn/app/Http/Controllers/Dev/{DashboardController,GameController,IapController,SettingController,PayoutController}.php`, `gpn/app/Http/Requests/Dev/{GameStoreRequest,GameUpdateRequest,IapRequest,SettingRequest}.php`, `gpn/app/Services/PayoutService.php`, `gpn/resources/views/developer/{dashboard,payouts,settings}.blade.php`, `gpn/resources/views/developer/games/{index,create,edit}.blade.php`
- Modify: `gpn/routes/web.php` (replace `/dev` placeholder)
- Test: `gpn/tests/Feature/GameManagementTest.php`, `gpn/tests/Feature/DevDashboardTest.php`

**Interfaces:**
- Consumes: `<x-portal-layout section="developer">`, `<x-area-chart>`, `KomisiTransaksi.payout_id` (Task 7), models/factories.
- Produces: routes `dev.dashboard`, `dev.games.index|create|store|edit|update|archive`, `dev.iaps.store|update`, `dev.settings.edit|update`, `dev.payouts.index`; `PayoutService::unpaidBalance(Developer $dev): string`, `PayoutService::unpaidRows(Developer $dev): Collection`, `PayoutService::process(Developer $dev, User $admin): ?Payout` (Task 10 consumes `process`); `ReportService`-independent inline dashboard stats (Task 9 keeps sales separate).

- [ ] **Step 1: Write the failing tests**

`gpn/tests/Feature/GameManagementTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\GameStatus;
use App\Models\{Game, Iap, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GameManagementTest extends TestCase
{
    use RefreshDatabase;

    private function dev(): User
    {
        return User::factory()->verifiedDeveloper()->create();
    }

    private function uploadPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Jelangkung Village',
            'description' => 'A horror adventure through a Javanese village.',
            'genre' => 'Horror',
            'price' => '75000',
            'cover' => UploadedFile::fake()->image('cover.jpg', 1280, 720),
            'screenshots' => [UploadedFile::fake()->image('s1.jpg'), UploadedFile::fake()->image('s2.jpg')],
            'trailer_url' => 'https://example.com/trailer',
            'file' => UploadedFile::fake()->create('game.zip', 1024, 'application/zip'),
        ], $overrides);
    }

    public function test_upload_creates_game_pending_review_with_files(): void // SRS 3.1.2.4 + 3-2.1
    {
        Storage::fake('public');
        $dev = $this->dev();
        $this->actingAs($dev)->post('/dev/games', $this->uploadPayload())->assertRedirect(route('dev.games.index'));

        $game = Game::sole();
        $this->assertSame(GameStatus::PendingReview, $game->status);
        $this->assertSame($dev->developer->id, $game->developer_id);
        Storage::disk('public')->assertExists($game->cover_path);
        Storage::disk('public')->assertExists($game->file_path);
        $this->assertSame(2, $game->screenshots()->count());
    }

    public function test_upload_validation_rejects_bad_file(): void // SRS 3.1.2.4 alternate
    {
        Storage::fake('public');
        $this->actingAs($this->dev())->from('/dev/games/create')
            ->post('/dev/games', $this->uploadPayload(['file' => UploadedFile::fake()->create('virus.exe.php', 10)]))
            ->assertSessionHasErrors('file');
        $this->assertSame(0, Game::count());
    }

    public function test_unverified_developer_is_blocked_from_portal(): void
    {
        $pending = User::factory()->developer()->create();
        $this->actingAs($pending)->get('/dev')->assertForbidden();
    }

    public function test_editing_published_game_returns_it_to_pending_review(): void // moderation-bypass fix
    {
        Storage::fake('public');
        $dev = $this->dev();
        $game = Game::factory()->for($dev->developer)->published()->create();
        $this->actingAs($dev)->put("/dev/games/{$game->id}", [
            'name' => 'New Title', 'description' => $game->description, 'genre' => $game->genre, 'price' => '80000',
        ])->assertRedirect();
        $this->assertSame(GameStatus::PendingReview, $game->fresh()->status);
    }

    public function test_price_only_change_keeps_game_published(): void // SRS 3.1.2.6
    {
        $dev = $this->dev();
        $game = Game::factory()->for($dev->developer)->published()->create(['price' => '50000.00']);
        $this->actingAs($dev)->put("/dev/games/{$game->id}", [
            'name' => $game->name, 'description' => $game->description, 'genre' => $game->genre, 'price' => '65000',
        ]);
        $fresh = $game->fresh();
        $this->assertSame(GameStatus::Published, $fresh->status);
        $this->assertSame('65000.00', $fresh->price);
    }

    public function test_archive_toggle_is_instant(): void // SRS 3.1.2.6
    {
        $dev = $this->dev();
        $game = Game::factory()->for($dev->developer)->published()->create();
        $this->actingAs($dev)->post("/dev/games/{$game->id}/archive");
        $this->assertSame(GameStatus::Archived, $game->fresh()->status);
        $this->actingAs($dev)->post("/dev/games/{$game->id}/archive");
        $this->assertSame(GameStatus::Published, $game->fresh()->status);
    }

    public function test_cross_developer_edit_is_forbidden(): void
    {
        $game = Game::factory()->published()->create();
        $this->actingAs($this->dev())->put("/dev/games/{$game->id}", [
            'name' => 'Hijack', 'description' => $game->description, 'genre' => $game->genre, 'price' => '1',
        ])->assertForbidden();
    }

    public function test_iap_create_edit_deactivate(): void // SRS 3.1.2.5 IAP
    {
        $dev = $this->dev();
        $game = Game::factory()->for($dev->developer)->published()->create();
        $this->actingAs($dev)->post("/dev/games/{$game->id}/iaps", [
            'name' => '1000 Coins', 'price' => '15000', 'type' => 'currency', 'description' => 'Coin pack',
        ])->assertRedirect();
        $iap = Iap::sole();
        $this->assertSame('15000.00', $iap->price);

        $this->actingAs($dev)->put("/dev/iaps/{$iap->id}", [
            'name' => '1000 Coins', 'price' => '12000', 'type' => 'currency', 'is_active' => '0',
        ]);
        $this->assertFalse($iap->fresh()->is_active);
    }

    public function test_settings_update_studio_and_payout_details(): void
    {
        $dev = $this->dev();
        $this->actingAs($dev)->post('/dev/settings', [
            'studio_name' => 'Studio Baru', 'portfolio' => 'https://portfolio.example',
            'payout_bank' => 'BCA', 'payout_account' => '1234567890',
        ])->assertRedirect();
        $this->assertSame('BCA', $dev->developer->fresh()->payout_bank);
    }
}
```

`gpn/tests/Feature/DevDashboardTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\{Download, KomisiTransaksi, Pembayaran, Transaksi, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DevDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_real_counts_and_series(): void
    {
        $dev = User::factory()->verifiedDeveloper()->create();
        $game = \App\Models\Game::factory()->for($dev->developer)->published()->create();
        $trx = Transaksi::factory()->success()->create(['game_id' => $game->id, 'total' => '100000.00']);
        Pembayaran::factory()->create(['transaksi_id' => $trx->id, 'paid_at' => now()]);
        KomisiTransaksi::factory()->create(['transaksi_id' => $trx->id, 'amount' => '20000.00', 'developer_amount' => '80000.00']);
        Download::factory()->create(['game_id' => $game->id]);

        $this->actingAs($dev)->get('/dev')
            ->assertOk()
            ->assertSee('Rp 100.000')   // gross
            ->assertSee('Rp 80.000')    // net
            ->assertViewHas('series', fn ($s) => count($s) === now()->daysInMonth);
    }

    public function test_payouts_page_shows_unpaid_balance(): void
    {
        $dev = User::factory()->verifiedDeveloper()->create();
        $game = \App\Models\Game::factory()->for($dev->developer)->published()->create();
        $trx = Transaksi::factory()->success()->create(['game_id' => $game->id]);
        KomisiTransaksi::factory()->create(['transaksi_id' => $trx->id, 'developer_amount' => '80000.00', 'payout_id' => null]);

        $this->actingAs($dev)->get('/dev/payouts')->assertSee('Rp 80.000');
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
docker run --rm -v "$PWD/gpn":/app -w /app php:8.4-cli php artisan test --filter="GameManagementTest|DevDashboardTest"
```

Expected: FAIL.

- [ ] **Step 3: PayoutService**

`app/Services/PayoutService.php`:

```php
<?php

namespace App\Services;

use App\Models\Developer;
use App\Models\KomisiTransaksi;
use App\Models\Payout;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PayoutService
{
    public function unpaidRows(Developer $developer): Collection
    {
        return KomisiTransaksi::whereNull('payout_id')
            ->whereHas('transaksi.game', fn ($q) => $q->where('developer_id', $developer->id))
            ->with('transaksi')
            ->get();
    }

    public function unpaidBalance(Developer $developer): string
    {
        return (string) $this->unpaidRows($developer)->sum(fn ($row) => (float) $row->developer_amount);
    }

    public function process(Developer $developer, User $admin): ?Payout // SRS 3.1.4.3
    {
        return DB::transaction(function () use ($developer, $admin) {
            $rows = $this->unpaidRows($developer);
            if ($rows->isEmpty()) {
                return null;
            }

            $payout = Payout::create([
                'developer_id' => $developer->id,
                'admin_id' => $admin->id,
                'period' => now()->format('Y-m'),
                'gross' => (string) $rows->sum(fn ($r) => (float) $r->transaksi->total),
                'commission' => (string) $rows->sum(fn ($r) => (float) $r->amount),
                'net' => (string) $rows->sum(fn ($r) => (float) $r->developer_amount),
                'status' => 'processed',
            ]);

            KomisiTransaksi::whereIn('id', $rows->pluck('id'))->update(['payout_id' => $payout->id]);

            return $payout;
        });
    }
}
```

- [ ] **Step 4: Controllers, requests, routes**

`GameStoreRequest` rules: `name` required max:150; `description` required max:5000; `genre` required `Rule::in(Game::GENRES)`; `price` required numeric min:0 max:999999999; `cover` required image mimes:jpg,jpeg,png,webp max:2048; `screenshots.*` image max:2048 (array max:5); `trailer_url` nullable url max:255; `file` exactly `['required', 'file', 'mimes:zip,7z,rar,gz', 'max:51200']` (Laravel's `mimes` validates the guessed extension, so `virus.exe.php` fails).

`GameUpdateRequest`: same minus `cover`/`file` (nullable — replace only if provided).

`Dev\GameController`:

```php
public function index(): View
{
    return view('developer.games.index', ['games' => auth()->user()->developer->games()->withCount('transaksis')->latest()->get()]);
}

public function store(GameStoreRequest $request): RedirectResponse // SRS 3.1.2.4
{
    $data = $request->validated();
    $game = DB::transaction(function () use ($request, $data) {
        $game = auth()->user()->developer->games()->create([
            ...Arr::only($data, ['name', 'description', 'genre']),
            'slug' => Str::slug($data['name']) . '-' . Str::lower(Str::random(4)),
            'price' => $data['price'],
            'trailer_url' => $data['trailer_url'] ?? null,
            'cover_path' => $request->file('cover')->store('covers', 'public'),
            'file_path' => $request->file('file')->store('installers', 'public'),
            'status' => GameStatus::PendingReview,
        ]);
        foreach ($request->file('screenshots', []) as $i => $shot) {
            $game->screenshots()->create(['path' => $shot->store('screenshots', 'public'), 'sort' => $i]);
        }
        return $game;
    });

    return redirect()->route('dev.games.index')->with('success', 'Game submitted for review.');
}

public function update(GameUpdateRequest $request, Game $game): RedirectResponse // SRS 3.1.2.5
{
    abort_unless($game->developer_id === auth()->user()->developer->id, 403);
    $data = $request->validated();

    $metadataChanged = $game->name !== $data['name']
        || $game->description !== $data['description']
        || $game->genre !== $data['genre']
        || $request->hasFile('cover') || $request->hasFile('file') || $request->hasFile('screenshots');

    DB::transaction(function () use ($request, $game, $data, $metadataChanged) {
        $updates = Arr::only($data, ['name', 'description', 'genre']) + ['price' => $data['price'], 'trailer_url' => $data['trailer_url'] ?? $game->trailer_url];
        if ($request->hasFile('cover')) { $updates['cover_path'] = $request->file('cover')->store('covers', 'public'); }
        if ($request->hasFile('file')) { $updates['file_path'] = $request->file('file')->store('installers', 'public'); }
        if ($metadataChanged && $game->status === GameStatus::Published) {
            $updates['status'] = GameStatus::PendingReview;
        }
        $game->update($updates);
        foreach ($request->file('screenshots', []) as $i => $shot) {
            $game->screenshots()->create(['path' => $shot->store('screenshots', 'public'), 'sort' => $game->screenshots()->max('sort') + 1 + $i]);
        }
    });

    $message = $metadataChanged && $game->fresh()->status === GameStatus::PendingReview
        ? 'Changes submitted for re-review.' : 'Game updated.';

    return redirect()->route('dev.games.index')->with('success', $message);
}

public function archive(Game $game): RedirectResponse // SRS 3.1.2.6
{
    abort_unless($game->developer_id === auth()->user()->developer->id, 403);
    $game->update(['status' => $game->status === GameStatus::Archived ? GameStatus::Published : GameStatus::Archived]);

    return back()->with('success', 'Availability updated.');
}
```

`Dev\GameController` also needs the two view-only actions: `create(): View` → `developer.games.create` (pass `Game::GENRES`), `edit(Game $game): View` → `developer.games.edit` (own-game 403 check, pass game + screenshots + `Game::GENRES`).

`Dev\IapController@store` / `@update`: own-game check; `IapRequest` rules `name` max:150, `price` numeric min:0, `type` in currency,item,upgrade,cosmetic, `description` nullable max:1000, `is_active` sometimes boolean.

`Dev\DashboardController@index`:

```php
public function index(): View
{
    $developer = auth()->user()->developer;
    $gameIds = $developer->games()->pluck('id');
    $month = now()->format('Y-m');

    $gross = Transaksi::whereIn('game_id', $gameIds)->where('status', 'success')->sum('total');
    $net = KomisiTransaksi::whereHas('transaksi', fn ($q) => $q->whereIn('game_id', $gameIds))->sum('developer_amount');
    $sales = Transaksi::whereIn('game_id', $gameIds)->where('status', 'success')->count();
    $downloads = Download::whereIn('game_id', $gameIds)->count();
    $recent = Transaksi::whereIn('game_id', $gameIds)->where('status', 'success')->with('game', 'user')->latest()->limit(10)->get();

    // Portable SQL only — no strftime (SQLite-only, the legacy bug). date() works in SQLite + MySQL.
    $daily = Pembayaran::whereHas('transaksi', fn ($q) => $q->whereIn('game_id', $gameIds))
        ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
        ->selectRaw('date(paid_at) as d, sum(amount) as s')
        ->groupBy('d')
        ->pluck('s', 'd');
    $series = collect(range(1, now()->daysInMonth))
        ->map(fn ($day) => ['label' => (string) $day, 'value' => (float) ($daily[now()->format('Y-m-') . str_pad((string) $day, 2, '0', STR_PAD_LEFT)] ?? 0)])
        ->all();

    return view('developer.dashboard', compact('gross', 'net', 'sales', 'downloads', 'recent', 'series'));
}
```

`Dev\SettingController@edit/@update` (`SettingRequest`: studio_name required max:120, portfolio nullable max:2000, payout_bank/account nullable max:100). `Dev\PayoutController@index`: `unpaidBalance`, payouts history for developer.

Routes (replace Task 4 `/dev` placeholder):

```php
Route::middleware(['auth', 'role:developer'])->prefix('dev')->name('dev.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/games', [GameController::class, 'index'])->name('games.index');
    Route::get('/games/create', [GameController::class, 'create'])->name('games.create');
    Route::post('/games', [GameController::class, 'store'])->name('games.store');
    Route::get('/games/{game}/edit', [GameController::class, 'edit'])->name('games.edit');
    Route::put('/games/{game}', [GameController::class, 'update'])->name('games.update');
    Route::post('/games/{game}/archive', [GameController::class, 'archive'])->name('games.archive');
    Route::post('/games/{game}/iaps', [IapController::class, 'store'])->name('iaps.store');
    Route::put('/iaps/{iap}', [IapController::class, 'update'])->name('iaps.update');
    Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
    Route::get('/payouts', [PayoutController::class, 'index'])->name('payouts.index');
});
```

- [ ] **Step 5: Views**

`developer/dashboard.blade.php` (Figma Dashboard `6:1976`): KPI cards row (Gross / Net / Sales / Downloads — `Money::format`, real numbers), `<x-area-chart :points="$series" />` with current-month label, recent sales table (buyer, game, total, date), game status list with status pills. Empty states honest ("No sales yet — your chart will fill in once players buy.").

`developer/games/index.blade.php`: table of own games (cover thumb, name, price, status pill + rejection feedback if rejected, downloads count, actions: Edit / Archive toggle / Add IAP), "Upload game" primary button. Empty state with upload CTA (SRS 3.1.2.6 alternate).

`developer/games/create.blade.php` + `edit.blade.php`: one shared form partial (`_form.blade.php`) — all fields with labels, current cover preview on edit, multi-file screenshots input, installer file input with note "zip/7z/rar/gz, max 50 MB". `@method('PUT')` on edit.

`developer/payouts.blade.php`: unpaid balance card, payouts history table (period, gross, commission, net, processed date).

`developer/settings.blade.php` (Figma Setting `142:216`): studio form.

- [ ] **Step 6: Run tests to verify they pass**

```bash
docker run --rm -v "$PWD/gpn":/app -w /app php:8.4-cli php artisan test --filter="GameManagementTest|DevDashboardTest"
```

Expected: PASS (11 tests).

- [ ] **Step 7: Commit**

```bash
git add -A && git commit -m "feat: developer portal — dashboard, game lifecycle with re-moderation, IAP, settings, payouts (SRS 3.1.2.4-6)"
```

---
### Task 9: Developer — sales report, promotions, tags

**Files:**
- Create: `gpn/app/Http/Controllers/Dev/{SalesController,PromotionController,TagController}.php`, `gpn/app/Http/Requests/Dev/{PromotionRequest,TagRequest}.php`, `gpn/app/Services/ReportService.php`, `gpn/resources/views/developer/{sales,promotion,tags}.blade.php`
- Modify: `gpn/routes/web.php` (dev group)
- Test: `gpn/tests/Feature/DevSalesTest.php`, `gpn/tests/Feature/PromotionTest.php`

**Interfaces:**
- Consumes: portal layout, `<x-area-chart>`, factories, `pembayarans.paid_at` settlement timestamp.
- Produces: `ReportService::developerSeries(Developer $dev, CarbonInterface $month, ?int $gameId): array` (points), `ReportService::developerKpis(Developer $dev, CarbonInterface $month, ?int $gameId): array{gross,commission,net,units,downloads}`, `ReportService::developerRows(Developer $dev, CarbonInterface $month, ?int $gameId): LengthAwarePaginator` (Task 10 reuses `ReportService` for admin reports); routes `dev.sales.index`, `dev.promotion.index|store|destroy`, `dev.tags.index|store|destroy|attach|detach`.

- [ ] **Step 1: Write the failing tests**

`gpn/tests/Feature/DevSalesTest.php` (SRS 3.1.5.1/3.1.5.2):

```php
<?php

namespace Tests\Feature;

use App\Models\{Download, Game, KomisiTransaksi, Pembayaran, Transaksi, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DevSalesTest extends TestCase
{
    use RefreshDatabase;

    private function settledSale(Game $game, string $total, string $paidAt): Transaksi
    {
        $trx = Transaksi::factory()->success()->create(['game_id' => $game->id, 'total' => $total]);
        Pembayaran::factory()->create(['transaksi_id' => $trx->id, 'paid_at' => $paidAt]);
        KomisiTransaksi::factory()->create([
            'transaksi_id' => $trx->id,
            'amount' => bcmul($total, '0.2', 2),
            'developer_amount' => bcmul($total, '0.8', 2),
        ]);
        return $trx;
    }

    public function test_sales_page_filters_by_month_and_game(): void // SRS 3.1.5.1
    {
        $dev = User::factory()->verifiedDeveloper()->create();
        $gameA = Game::factory()->for($dev->developer)->published()->create(['name' => 'Game A']);
        $gameB = Game::factory()->for($dev->developer)->published()->create(['name' => 'Game B']);
        $this->settledSale($gameA, '100000.00', '2026-03-10 12:00:00');
        $this->settledSale($gameB, '50000.00', '2026-04-02 12:00:00');

        $this->actingAs($dev)->get('/dev/sales?month=2026-03')
            ->assertOk()
            ->assertSee('Rp 100.000')
            ->assertDontSee('Rp 50.000');

        $this->actingAs($dev)->get('/dev/sales?month=2026-03&game_id=' . $gameB->id)
            ->assertSee('No sales in this period'); // SRS alternate flow
    }

    public function test_sales_kpis_show_gross_commission_net(): void // SRS 3.1.5.2
    {
        $dev = User::factory()->verifiedDeveloper()->create();
        $game = Game::factory()->for($dev->developer)->published()->create();
        $this->settledSale($game, '100000.00', now()->toDateTimeString());
        Download::factory()->count(3)->create(['game_id' => $game->id]);

        $this->actingAs($dev)->get('/dev/sales?month=' . now()->format('Y-m'))
            ->assertSee('Rp 100.000')
            ->assertSee('Rp 20.000')
            ->assertSee('Rp 80.000');
    }

    public function test_other_developers_sales_are_hidden(): void
    {
        $dev = User::factory()->verifiedDeveloper()->create();
        $other = User::factory()->verifiedDeveloper()->create();
        $foreign = Game::factory()->for($other->developer)->published()->create();
        $this->settledSale($foreign, '999000.00', now()->toDateTimeString());

        $this->actingAs($dev)->get('/dev/sales')->assertDontSee('Rp 999.000');
    }
}
```

`gpn/tests/Feature/PromotionTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\{Game, Promotion, Transaksi, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotionTest extends TestCase
{
    use RefreshDatabase;

    private function devWithGame(): array
    {
        $dev = User::factory()->verifiedDeveloper()->create();
        return [$dev, Game::factory()->for($dev->developer)->published()->create(['price' => '100000.00'])];
    }

    public function test_create_promotion_and_store_shows_discount(): void
    {
        [$dev, $game] = $this->devWithGame();
        $this->actingAs($dev)->post('/dev/promotion', [
            'game_id' => $game->id, 'discount_percent' => 30,
            'starts_at' => now()->subHour()->toDateTimeString(),
            'ends_at' => now()->addWeek()->toDateTimeString(),
        ])->assertRedirect(route('dev.promotion.index'));

        $this->assertSame('70000.00', $game->fresh()->effectivePrice());
        $this->get("/store/{$game->slug}")->assertSee('Rp 70.000')->assertSee('Rp 100.000');
    }

    public function test_overlapping_promotion_is_rejected(): void
    {
        [$dev, $game] = $this->devWithGame();
        Promotion::factory()->create(['game_id' => $game->id, 'starts_at' => now(), 'ends_at' => now()->addWeek()]);
        $this->actingAs($dev)->from('/dev/promotion')->post('/dev/promotion', [
            'game_id' => $game->id, 'discount_percent' => 10,
            'starts_at' => now()->addDays(3)->toDateTimeString(),
            'ends_at' => now()->addDays(10)->toDateTimeString(),
        ])->assertSessionHasErrors('ends_at');
    }

    public function test_expired_promotion_no_longer_applies(): void
    {
        [$dev, $game] = $this->devWithGame();
        Promotion::factory()->create(['game_id' => $game->id, 'discount_percent' => 50, 'starts_at' => now()->subWeeks(2), 'ends_at' => now()->subWeek()]);
        $this->assertSame('100000.00', $game->fresh()->effectivePrice());
    }

    public function test_promotion_requires_own_game(): void
    {
        [, $game] = $this->devWithGame();
        $other = User::factory()->verifiedDeveloper()->create();
        $this->actingAs($other)->post('/dev/promotion', [
            'game_id' => $game->id, 'discount_percent' => 10,
            'starts_at' => now()->toDateTimeString(), 'ends_at' => now()->addDay()->toDateTimeString(),
        ])->assertForbidden();
    }

    public function test_tags_attach_and_detach(): void
    {
        $dev = User::factory()->verifiedDeveloper()->create();
        $game = Game::factory()->for($dev->developer)->published()->create();
        $this->actingAs($dev)->post('/dev/tags', ['name' => 'pixel-art'])->assertRedirect();
        $tag = \App\Models\Tag::sole();

        $this->actingAs($dev)->post("/dev/tags/{$tag->id}/attach", ['game_id' => $game->id]);
        $this->assertTrue($game->fresh()->tags->contains($tag));
        $this->actingAs($dev)->post("/dev/tags/{$tag->id}/detach", ['game_id' => $game->id]);
        $this->assertFalse($game->fresh()->tags->contains($tag));

        $this->get('/store?tag=pixel-art')->assertDontSee($game->name);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
docker run --rm -v "$PWD/gpn":/app -w /app php:8.4-cli php artisan test --filter="DevSalesTest|PromotionTest"
```

Expected: FAIL.

- [ ] **Step 3: ReportService (developer half)**

`app/Services/ReportService.php`:

```php
<?php

namespace App\Services;

use App\Models\Developer;
use App\Models\Download;
use App\Models\KomisiTransaksi;
use App\Models\Pembayaran;
use App\Models\Transaksi;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReportService
{
    private function settledQuery(Developer $dev, CarbonInterface $month, ?int $gameId)
    {
        return Transaksi::where('status', 'success')
            ->whereHas('game', fn ($q) => $q->where('developer_id', $dev->id))
            ->when($gameId, fn ($q) => $q->where('game_id', $gameId))
            ->whereHas('pembayaran', fn ($q) => $q->whereBetween('paid_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()]));
    }

    public function developerRows(Developer $dev, CarbonInterface $month, ?int $gameId): LengthAwarePaginator
    {
        return $this->settledQuery($dev, $month, $gameId)
            ->with('game', 'user', 'pembayaran', 'komisiTransaksi')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
    }

    public function developerKpis(Developer $dev, CarbonInterface $month, ?int $gameId): array
    {
        $rows = $this->settledQuery($dev, $month, $gameId)->with('komisiTransaksi', 'game')->get();
        $gameIds = $dev->games()->when($gameId, fn ($q) => $q->where('id', $gameId))->pluck('id');

        return [
            'gross' => (string) $rows->sum(fn ($t) => (float) $t->total),
            'commission' => (string) $rows->sum(fn ($t) => (float) ($t->komisiTransaksi->amount ?? 0)),
            'net' => (string) $rows->sum(fn ($t) => (float) ($t->komisiTransaksi->developer_amount ?? 0)),
            'units' => $rows->count(),
            'downloads' => Download::whereIn('game_id', $gameIds)
                ->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
                ->count(),
        ];
    }

    public function developerSeries(Developer $dev, CarbonInterface $month, ?int $gameId): array
    {
        $daily = Pembayaran::whereHas('transaksi', fn ($q) => $q->whereHas('game', fn ($g) => $g->where('developer_id', $dev->id))->when($gameId, fn ($q) => $q->where('game_id', $gameId)))
            ->whereBetween('paid_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->selectRaw('date(paid_at) as d, sum(amount) as s')
            ->groupBy('d')
            ->pluck('s', 'd');

        return collect(range(1, $month->daysInMonth))->map(fn ($day) => [
            'label' => (string) $day,
            'value' => (float) ($daily[$month->format('Y-m-') . str_pad((string) $day, 2, '0', STR_PAD_LEFT)] ?? 0),
        ])->all();
    }
}
```

- [ ] **Step 4: Controllers, requests, routes**

`Dev\SalesController@index`: parse `month` (default current, validate `date_format:Y-m`) with `Carbon::parse($month . '-01')` — **never** `Carbon::createFromFormat('Y-m', ...)`, which fills missing parts with the current date/time and breaks month boundaries; `game_id` (nullable, must belong to developer); call ReportService; pass `kpis`, `series`, `rows`, developer's games for the filter dropdown.

`PromotionRequest` rules: `game_id` required exists (ownership checked in controller via policy-ish abort), `discount_percent` required integer 1–90, `starts_at` required date, `ends_at` required date after:starts_at — plus an `after` overlap check with a closure rule on `ends_at`:

```php
'ends_at' => ['required', 'date', 'after:starts_at', function ($attribute, $value, $fail) {
    $overlap = \App\Models\Promotion::where('game_id', $this->input('game_id'))
        ->where('ends_at', '>', $this->input('starts_at'))
        ->where('starts_at', '<', $value)
        ->exists();
    if ($overlap) {
        $fail('This game already has a promotion in that period.');
    }
}],
```

`Dev\PromotionController@index` (own games' promotions with computed status: scheduled/active/ended), `@store` (abort 403 unless own game; create + flash), `@destroy` (own + only future promotions deletable).

`TagRequest`: `name` required max:50 alpha-dash unique:tags,name. `Dev\TagController@index` (all tags + own games for attach UI), `@store` (firstOrCreate), `@destroy` (only unused tags), `@attach/@detach` (`game_id` validated own game; `syncWithoutDetaching` / `detach`).

Routes (inside dev group):

```php
Route::get('/sales', [SalesController::class, 'index'])->name('sales.index');
Route::get('/promotion', [PromotionController::class, 'index'])->name('promotion.index');
Route::post('/promotion', [PromotionController::class, 'store'])->name('promotion.store');
Route::delete('/promotion/{promotion}', [PromotionController::class, 'destroy'])->name('promotion.destroy');
Route::get('/tags', [TagController::class, 'index'])->name('tags.index');
Route::post('/tags', [TagController::class, 'store'])->name('tags.store');
Route::delete('/tags/{tag}', [TagController::class, 'destroy'])->name('tags.destroy');
Route::post('/tags/{tag}/attach', [TagController::class, 'attach'])->name('tags.attach');
Route::post('/tags/{tag}/detach', [TagController::class, 'detach'])->name('tags.detach');
```

- [ ] **Step 5: Views**

`developer/sales.blade.php` (Figma Sales `161:138`): filter bar (month `<input type="month" name="month">`, game select, Apply button), KPI cards (Gross / Commission / Net / Units / Downloads), `<x-area-chart :points="$series" />`, transactions table (date, order, buyer, game, gross, commission, net) with `{{ $rows->links() }}`; empty state "No sales in this period".

`developer/promotion.blade.php` (Figma Promotion `74:46`): create form (game select of own published games, percent, datetime-local start/end), list with status pills (Scheduled gray / Active green / Ended faint), delete button on scheduled rows.

`developer/tags.blade.php` (Figma Tags `74:212`): create-tag form, tag chips list with attach dropdown (own games) and detach buttons, delete on unused tags.

- [ ] **Step 6: Run tests to verify they pass**

```bash
docker run --rm -v "$PWD/gpn":/app -w /app php:8.4-cli php artisan test --filter="DevSalesTest|PromotionTest"
```

Expected: PASS (8 tests).

- [ ] **Step 7: Commit**

```bash
git add -A && git commit -m "feat: developer sales report, promotions with overlap guard, tags (SRS 3.1.5.1-2, F-07)"
```

---
### Task 10: Admin panel — verification, moderation, commission, payouts, reports

**Files:**
- Create: `gpn/app/Http/Controllers/Admin/{DashboardController,VerificationController,ModerationController,CommissionController,PayoutController,ReportController}.php`, `gpn/app/Http/Requests/Admin/{VerifyRequest,ModerateRequest,CommissionRequest}.php`, `gpn/resources/views/admin/{dashboard,commission,payouts,reports}.blade.php`, `gpn/resources/views/admin/verification/index.blade.php`, `gpn/resources/views/admin/moderation/{index,show}.blade.php`
- Modify: `gpn/routes/web.php` (replace `/admin` placeholder), `gpn/app/Services/ReportService.php` (add admin methods)
- Test: `gpn/tests/Feature/VerificationTest.php`, `gpn/tests/Feature/ModerationTest.php`, `gpn/tests/Feature/CommissionPayoutTest.php`, `gpn/tests/Feature/ReportsTest.php`

**Interfaces:**
- Consumes: `<x-portal-layout section="admin">`, `PayoutService` (Task 8), `ReportService` (Task 9 — this task adds `adminTransactions(array $filters): LengthAwarePaginator` and `adminCommission(CarbonInterface $month): Collection`).
- Produces: routes `admin.dashboard`, `admin.verification.index|update`, `admin.moderation.index|show|update`, `admin.commission.index|store`, `admin.payouts.index|store`, `admin.reports.index`. Task 13's seeder creates the admin via `User::factory()->admin()`.

- [ ] **Step 1: Write the failing tests**

`gpn/tests/Feature/VerificationTest.php` (SRS 3.1.1.4 + Review Focus 5):

```php
<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_approves_pending_developer_who_can_then_log_in(): void // SRS 3.1.1.4
    {
        $admin = User::factory()->admin()->create();
        $dev = User::factory()->developer()->create();

        $this->actingAs($admin)->post("/admin/verification/{$dev->id}", ['action' => 'approve'])->assertRedirect();
        $this->assertSame(UserStatus::Active, $dev->fresh()->status);
        $this->post('/login', ['email' => $dev->email, 'password' => 'password'])->assertRedirect('/dev');
    }

    public function test_reject_requires_reason_and_blocks_login(): void // SRS 3.1.1.4 alternate
    {
        $admin = User::factory()->admin()->create();
        $dev = User::factory()->developer()->create();

        $this->actingAs($admin)->from('/admin/verification')->post("/admin/verification/{$dev->id}", ['action' => 'reject'])
            ->assertSessionHasErrors('reason');
        $this->actingAs($admin)->post("/admin/verification/{$dev->id}", ['action' => 'reject', 'reason' => 'Invalid documents'])
            ->assertRedirect();
        $this->assertSame(UserStatus::Rejected, $dev->fresh()->status);
        $this->assertSame('Invalid documents', $dev->fresh()->rejection_reason);
    }

    public function test_rejected_developer_can_be_reapproved(): void // Review Focus 5
    {
        $admin = User::factory()->admin()->create();
        $dev = User::factory()->developer()->create(['status' => UserStatus::Rejected, 'rejection_reason' => 'x']);

        $this->actingAs($admin)->get('/admin/verification?tab=rejected')->assertSee($dev->email);
        $this->actingAs($admin)->post("/admin/verification/{$dev->id}", ['action' => 'approve']);
        $this->assertSame(UserStatus::Active, $dev->fresh()->status);
        $this->assertNull($dev->fresh()->rejection_reason);
    }

    public function test_non_admin_cannot_verify(): void
    {
        $dev = User::factory()->developer()->create();
        $target = User::factory()->developer()->create();
        $this->actingAs($dev)->post("/admin/verification/{$target->id}", ['action' => 'approve'])->assertForbidden();
    }
}
```

`gpn/tests/Feature/ModerationTest.php` (SRS 3.1.4.1 + 3-2.3):

```php
<?php

namespace Tests\Feature;

use App\Enums\GameStatus;
use App\Models\{Game, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_queue_lists_pending_games_and_approve_publishes(): void // SRS 3-2.3
    {
        $admin = User::factory()->admin()->create();
        $pending = Game::factory()->create(['status' => GameStatus::PendingReview, 'name' => 'Sundel Bolong']);
        Game::factory()->published()->create(['name' => 'Already Live']);

        $this->actingAs($admin)->get('/admin/moderation')
            ->assertSee('Sundel Bolong')->assertDontSee('Already Live');

        $this->actingAs($admin)->post("/admin/moderation/{$pending->id}", ['action' => 'approve']);
        $this->assertSame(GameStatus::Published, $pending->fresh()->status);
        $this->get('/store')->assertSee('Sundel Bolong');
    }

    public function test_reject_requires_feedback_and_shows_it_to_developer(): void // SRS 3-2.3 reject path
    {
        $admin = User::factory()->admin()->create();
        $dev = User::factory()->verifiedDeveloper()->create();
        $game = Game::factory()->for($dev->developer)->create(['status' => GameStatus::PendingReview]);

        $this->actingAs($admin)->from("/admin/moderation/{$game->id}")->post("/admin/moderation/{$game->id}", ['action' => 'reject'])
            ->assertSessionHasErrors('feedback');
        $this->actingAs($admin)->post("/admin/moderation/{$game->id}", ['action' => 'reject', 'feedback' => 'Installer corrupted'])
            ->assertRedirect();

        $this->assertSame(GameStatus::Rejected, $game->fresh()->status);
        $this->actingAs($dev)->get('/dev/games')->assertSee('Installer corrupted');
    }

    public function test_resubmitted_game_returns_to_queue(): void
    {
        $admin = User::factory()->admin()->create();
        $dev = User::factory()->verifiedDeveloper()->create();
        $game = Game::factory()->for($dev->developer)->create(['status' => GameStatus::Rejected]);

        $this->actingAs($dev)->put("/dev/games/{$game->id}", [
            'name' => $game->name . ' fixed', 'description' => $game->description, 'genre' => $game->genre, 'price' => '10000',
        ]);
        $this->assertSame(GameStatus::PendingReview, $game->fresh()->status);
        $this->actingAs($admin)->get('/admin/moderation')->assertSee($game->fresh()->name);
    }
}
```

`gpn/tests/Feature/CommissionPayoutTest.php` (SRS 3.1.4.2/3.1.4.3):

```php
<?php

namespace Tests\Feature;

use App\Enums\TransaksiStatus;
use App\Models\{Developer, Game, Komisi, KomisiTransaksi, Payout, Transaksi, User};
use App\Services\PayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommissionPayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_commission_schedule_is_append_only_and_effective_dated(): void // SRS 3.1.4.2
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->post('/admin/commission', ['percentage' => '15.5', 'effective_from' => today()->addWeek()->toDateString()])
            ->assertRedirect();
        $this->assertSame(1, Komisi::count());
        // Settlement today still falls back to default 20% since 15.5% isn't effective yet
        $this->assertSame('20.00', app(\App\Services\CommissionService::class)->percentageFor(now()));
        $this->assertSame('15.50', app(\App\Services\CommissionService::class)->percentageFor(now()->addMonth()));
    }

    public function test_payout_sums_unpaid_and_stamps_rows(): void // SRS 3.1.4.3
    {
        $admin = User::factory()->admin()->create();
        $dev = User::factory()->verifiedDeveloper()->create();
        $game = Game::factory()->for($dev->developer)->published()->create();
        foreach (['100000.00', '50000.00'] as $total) {
            $trx = Transaksi::factory()->success()->create(['game_id' => $game->id, 'total' => $total]);
            KomisiTransaksi::factory()->create([
                'transaksi_id' => $trx->id,
                'amount' => bcmul($total, '0.2', 2),
                'developer_amount' => bcmul($total, '0.8', 2),
            ]);
        }

        $this->actingAs($admin)->post("/admin/payouts/{$dev->developer->id}")->assertRedirect();

        $payout = Payout::sole();
        $this->assertSame('150000.00', $payout->gross);
        $this->assertSame('30000.00', $payout->commission);
        $this->assertSame('120000.00', $payout->net);
        $this->assertSame(0, KomisiTransaksi::whereNull('payout_id')->count());
        $this->assertSame('0', app(PayoutService::class)->unpaidBalance($dev->developer));
    }

    public function test_empty_payout_shows_message(): void // SRS 3.1.4.3 alternate
    {
        $admin = User::factory()->admin()->create();
        $dev = User::factory()->verifiedDeveloper()->create();
        $this->actingAs($admin)->post("/admin/payouts/{$dev->developer->id}")
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertSame(0, Payout::count());
    }

    public function test_payouts_page_lists_balances_per_developer(): void
    {
        $admin = User::factory()->admin()->create();
        $dev = User::factory()->verifiedDeveloper()->create();
        $game = Game::factory()->for($dev->developer)->published()->create();
        $trx = Transaksi::factory()->success()->create(['game_id' => $game->id]);
        KomisiTransaksi::factory()->create(['transaksi_id' => $trx->id, 'developer_amount' => '64000.00']);

        $this->actingAs($admin)->get('/admin/payouts')
            ->assertSee($dev->developer->studio_name)
            ->assertSee('Rp 64.000');
    }
}
```

`gpn/tests/Feature/ReportsTest.php` (SRS 3.1.5.3/4/5 + Review Focus 4):

```php
<?php

namespace Tests\Feature;

use App\Models\{Game, KomisiTransaksi, Pembayaran, Transaksi, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    private function settledAt(Game $game, string $total, string $paidAt, string $status = 'success'): Transaksi
    {
        $trx = Transaksi::factory()->create(['game_id' => $game->id, 'total' => $total, 'status' => $status]);
        if ($status === 'success') {
            Pembayaran::factory()->create(['transaksi_id' => $trx->id, 'paid_at' => $paidAt]);
            KomisiTransaksi::factory()->create([
                'transaksi_id' => $trx->id,
                'amount' => bcmul($total, '0.2', 2),
                'developer_amount' => bcmul($total, '0.8', 2),
            ]);
        }
        return $trx;
    }

    public function test_month_filter_includes_boundaries(): void // Review Focus 4 — SRS 3.1.5.3
    {
        $admin = User::factory()->admin()->create();
        $game = Game::factory()->published()->create();
        $this->settledAt($game, '10000.00', '2026-03-01 00:00:00');
        $this->settledAt($game, '20000.00', '2026-03-31 23:59:59');
        $this->settledAt($game, '40000.00', '2026-04-01 00:00:00');

        $this->actingAs($admin)->get('/admin/reports?tab=transactions&month=2026-03')
            ->assertSee('Rp 30.000')
            ->assertDontSee('Rp 40.000');
    }

    public function test_transaction_report_filters_by_status(): void // SRS 3.1.5.3
    {
        $admin = User::factory()->admin()->create();
        $game = Game::factory()->published()->create();
        $this->settledAt($game, '10000.00', now()->toDateTimeString());
        Transaksi::factory()->failed()->create(['game_id' => $game->id, 'total' => '99000.00']);

        $this->actingAs($admin)->get('/admin/reports?tab=transactions&status=failed')
            ->assertSee('Rp 99.000')
            ->assertDontSee('Rp 10.000');
    }

    public function test_commission_report_groups_by_developer(): void // SRS 3.1.5.4
    {
        $admin = User::factory()->admin()->create();
        $dev = User::factory()->verifiedDeveloper()->create();
        $game = Game::factory()->for($dev->developer)->published()->create();
        $this->settledAt($game, '100000.00', now()->toDateTimeString());

        $this->actingAs($admin)->get('/admin/reports?tab=commission&month=' . now()->format('Y-m'))
            ->assertSee($dev->developer->studio_name)
            ->assertSee('Rp 20.000')
            ->assertSee('Rp 80.000');
    }

    public function test_empty_report_state(): void // SRS 3.1.5.3 alternate
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get('/admin/reports?tab=transactions&month=2020-01')
            ->assertSee('No transactions found');
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
docker run --rm -v "$PWD/gpn":/app -w /app php:8.4-cli php artisan test --filter="VerificationTest|ModerationTest|CommissionPayoutTest|ReportsTest"
```

Expected: FAIL.

- [ ] **Step 3: Controllers, requests, routes**

`Admin\DashboardController@index`: counts — pending developers (`User::where('role','developer')->where('status','pending')->count()`), pending games (`pending_review`), total users, today's transactions, commission this month (`KomisiTransaksi` joined to `pembayarans.paid_at` month, sum `amount`).

`VerifyRequest`: `action` in approve,reject; `reason` required_if:action,reject nullable max:500. `Admin\VerificationController@index`: `$tab = request('tab', 'pending')`; list developers by status; `@update(User $user)` (abort 404 unless role developer): approve → `status active, rejection_reason null`; reject → `status rejected, rejection_reason = reason`; flash; back. SRS 3.1.1.4.

`ModerateRequest`: `action` in approve,reject; `feedback` required_if:action,reject nullable max:1000. `Admin\ModerationController@index` (pending_review with developer, oldest first), `@show(Game $game)` (full detail incl. screenshots, file size via `Storage::disk('public')->size()`), `@update`: approve → Published + clear feedback; reject → Rejected + feedback; flash. SRS 3.1.4.1.

`CommissionRequest`: `percentage` required numeric 0–50 between:0,50; `effective_from` required date. `Admin\CommissionController@index` (current rate via `CommissionService::percentageFor(now())`, schedule list desc) + `@store` (create row; flash). Append-only — no edit/delete routes. SRS 3.1.4.2.

`Admin\PayoutController@index`: developers with unpaid balances (`PayoutService::unpaidBalance` per developer with any `komisi_transaksi` rows) + payout history latest 20. `@store(Developer $developer)`: `$payout = app(PayoutService::class)->process($developer, $request->user())`; null → `back()->with('error', 'No payable balance for this developer.')`; else success flash with net. SRS 3.1.4.3.

`ReportService` additions:

```php
    public function adminTransactions(array $filters): LengthAwarePaginator // SRS 3.1.5.3
    {
        $month = $filters['month'] ?? null; // 'Y-m' or null
        return Transaksi::with('user', 'game', 'pembayaran')
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($month, function ($q) use ($month) {
                $m = \Carbon\Carbon::parse($month . '-01'); // never createFromFormat('Y-m') — fills with current day/time
                $q->where(function ($qq) use ($m) {
                    $qq->whereHas('pembayaran', fn ($p) => $p->whereBetween('paid_at', [$m->copy()->startOfMonth(), $m->copy()->endOfMonth()]))
                       ->orWhere(fn ($n) => $n->where('status', '!=', 'success')->whereBetween('transaksis.created_at', [$m->copy()->startOfMonth(), $m->copy()->endOfMonth()]));
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();
    }

    public function adminCommission(CarbonInterface $month): Collection // SRS 3.1.5.4 — per developer
    {
        return KomisiTransaksi::whereHas('transaksi.pembayaran', fn ($q) => $q->whereBetween('paid_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()]))
            ->with('transaksi.game.developer')
            ->get()
            ->groupBy(fn ($row) => $row->transaksi->game->developer->studio_name)
            ->map(fn ($rows) => [
                'gross' => (string) $rows->sum(fn ($r) => (float) $r->transaksi->total),
                'commission' => (string) $rows->sum(fn ($r) => (float) $r->amount),
                'net' => (string) $rows->sum(fn ($r) => (float) $r->developer_amount),
                'count' => $rows->count(),
            ]);
    }
```

`Admin\ReportController@index`: `$tab` in transactions|commission (default transactions); transactions tab → `adminTransactions` + totals row (sum of filtered page query — compute via a separate aggregate query with the same filters) ; commission tab → `adminCommission(Carbon)`; month param validated `date_format:Y-m` nullable.

Routes (replace `/admin` placeholder):

```php
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/verification', [VerificationController::class, 'index'])->name('verification.index');
    Route::post('/verification/{user}', [VerificationController::class, 'update'])->name('verification.update');
    Route::get('/moderation', [ModerationController::class, 'index'])->name('moderation.index');
    Route::get('/moderation/{game}', [ModerationController::class, 'show'])->name('moderation.show');
    Route::post('/moderation/{game}', [ModerationController::class, 'update'])->name('moderation.update');
    Route::get('/commission', [CommissionController::class, 'index'])->name('commission.index');
    Route::post('/commission', [CommissionController::class, 'store'])->name('commission.store');
    Route::get('/payouts', [AdminPayoutController::class, 'index'])->name('payouts.index');
    Route::post('/payouts/{developer}', [AdminPayoutController::class, 'store'])->name('payouts.store');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
});
```

- [ ] **Step 4: Views** (all `<x-portal-layout section="admin">`, same dark table language as dev Sales screen)

`admin/dashboard.blade.php`: KPI cards (Pending developers badge-linked to queue, Pending games, Users, Today's transactions, Commission this month).

`admin/verification/index.blade.php`: tabs Pending / Rejected (`?tab=`), table (name, email, studio, registered date) with inline Approve `.btn-primary` and Reject form (reason textarea revealed per-row, required) — SRS 3.1.1.4. Rejected tab shows stored reason + Approve button.

`admin/moderation/index.blade.php`: queue table (game, studio, submitted date, genre, price) → detail. `admin/moderation/show.blade.php`: full game detail (cover, screenshots, description, IAPs, file size), Approve button, Reject form with feedback textarea.

`admin/commission.blade.php`: current rate big number, schedule form (percent, effective_from), history table — visually append-only, no edit buttons.

`admin/payouts.blade.php`: unpaid balances table (studio, bank, unpaid, Process button → confirm), history table below.

`admin/reports.blade.php`: tabs Transactions / Commission; filter bar (month, status on transactions tab); transactions table + totals footer; commission tab per-developer table; empty states "No transactions found" / "No commission data".

- [ ] **Step 5: Run tests to verify they pass**

```bash
docker run --rm -v "$PWD/gpn":/app -w /app php:8.4-cli php artisan test --filter="VerificationTest|ModerationTest|CommissionPayoutTest|ReportsTest"
```

Expected: PASS (14 tests).

- [ ] **Step 6: Commit**

```bash
git add -A && git commit -m "feat: admin panel — verification, moderation, commission schedule, payouts, reports (SRS 3.1.4, 3.1.5)"
```

---
### Task 11: Community — feed, posts, voting, trending hashtags

**Files:**
- Create: `gpn/app/Http/Controllers/{CommunityController,VoteController}.php`, `gpn/app/Http/Requests/PostRequest.php`, `gpn/resources/views/community/index.blade.php`
- Modify: `gpn/routes/web.php`
- Test: `gpn/tests/Feature/CommunityTest.php`

**Interfaces:**
- Consumes: `<x-app-layout>`, Post/PostVote/Download models.
- Produces: routes `community.index`, `community.posts.store`, `posts.vote` (`POST /posts/{post}/vote`).

- [ ] **Step 1: Write the failing test**

`gpn/tests/Feature/CommunityTest.php` (SRS §4.1 community):

```php
<?php

namespace Tests\Feature;

use App\Models\{Download, Game, Post, PostVote, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunityTest extends TestCase
{
    use RefreshDatabase;

    public function test_feed_lists_posts_newest_first_with_real_score(): void
    {
        $old = Post::factory()->create(['body' => 'Old post', 'created_at' => now()->subDay()]);
        $new = Post::factory()->create(['body' => 'Fresh post', 'created_at' => now()]);
        PostVote::factory()->create(['post_id' => $old->id, 'value' => 1]);
        PostVote::factory()->create(['post_id' => $old->id, 'value' => 1]);

        $this->get('/community')
            ->assertOk()
            ->assertSeeInOrder(['Fresh post', 'Old post'])
            ->assertSee('2'); // score
    }

    public function test_posting_requires_auth_and_parses_hashtags(): void
    {
        $this->post('/community/posts', ['body' => 'hello #gamelokal'])->assertRedirect(route('login'));

        $user = User::factory()->create();
        $this->actingAs($user)->post('/community/posts', ['body' => 'Rilis baru! #gamelokal #HorrorID'])
            ->assertRedirect(route('community.index'));
        $post = Post::sole();
        $this->assertSame(['gamelokal', 'horrorid'], $post->hashtags);
    }

    public function test_vote_toggle_flip_and_remove(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)->post("/posts/{$post->id}/vote", ['value' => 1]);
        $this->assertSame(1, $post->fresh()->score);

        $this->actingAs($user)->post("/posts/{$post->id}/vote", ['value' => -1]);
        $this->assertSame(-1, $post->fresh()->score);

        $this->actingAs($user)->post("/posts/{$post->id}/vote", ['value' => -1]);
        $this->assertSame(0, $post->fresh()->score);
        $this->assertSame(0, PostVote::count());
    }

    public function test_trending_counts_last_7_days_only(): void
    {
        // body must NOT contain the tag text — the feed renders bodies and would trip assertDontSee
        Post::factory()->create(['body' => 'old chatter', 'hashtags' => ['retro'], 'created_at' => now()->subDays(10)]);
        Post::factory()->create(['body' => 'b #gamelokal', 'hashtags' => ['gamelokal']]);

        $this->get('/community')
            ->assertSee('#gamelokal')
            ->assertDontSee('#retro');
    }

    public function test_right_rail_shows_top_downloaded_this_month(): void
    {
        $hot = Game::factory()->published()->create(['name' => 'Viral Game']);
        $cold = Game::factory()->published()->create(['name' => 'Quiet Game']);
        Download::factory()->count(3)->create(['game_id' => $hot->id, 'created_at' => now()]);
        Download::factory()->create(['game_id' => $cold->id, 'created_at' => now()->subMonths(2)]);

        $this->get('/community')
            ->assertSee('Viral Game')
            ->assertDontSee('Quiet Game');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
docker run --rm -v "$PWD/gpn":/app -w /app php:8.4-cli php artisan test --filter=CommunityTest
```

Expected: FAIL.

- [ ] **Step 3: Controllers, request, routes**

`PostRequest`: `body` required string max:2000; `image` nullable image max:2048.

`CommunityController@index`:

```php
public function index(): View
{
    $posts = Post::with('user', 'votes')->latest()->paginate(20); // score via Post::getScoreAttribute()

    $trending = Post::where('created_at', '>=', now()->subDays(7))
        ->get()                 // hydrate models so the hashtags 'array' cast applies
        ->pluck('hashtags')
        ->flatten()
        ->countBy()
        ->sortDesc()
        ->take(8);

    $topGames = Game::published()
        ->withCount(['downloads' => fn ($q) => $q->where('created_at', '>=', now()->startOfMonth())])
        ->orderByDesc('downloads_count')
        ->limit(5)
        ->get();

    return view('community.index', compact('posts', 'trending', 'topGames'));
}

public function store(PostRequest $request): RedirectResponse
{
    preg_match_all('/#([\p{L}\p{N}_]+)/u', $request->validated('body'), $m);
    $hashtags = array_values(array_unique(array_map(fn ($t) => mb_strtolower($t), $m[1])));

    Post::create([
        'user_id' => $request->user()->id,
        'body' => $request->validated('body'),
        'image_path' => $request->hasFile('image') ? $request->file('image')->store('posts', 'public') : null,
        'hashtags' => $hashtags,
    ]);

    return redirect()->route('community.index')->with('success', 'Posted!');
}
```

`VoteController@store`: `$request->validate(['value' => 'required|in:-1,1'])`; existing vote: same value → delete (toggle off); different → update value; none → create. `return back()`.

Routes: `GET /community` (public, name `community.index`); auth group: `POST /community/posts`, `POST /posts/{post}/vote`.

- [ ] **Step 4: View**

`community/index.blade.php` (Figma Community `181:529`): hero band (gradient accent card with "Community" heading + subtitle), 3-column layout: left sidebar (trending `#tag` list with counts, each linking `?tag=` filter — implement filter: `when(request('tag'), fn ($q) => $q->whereJsonContains('hashtags', request('tag')))`), center feed (create box when authed — textarea + image upload + Post button; post cards with author initial avatar, name, relative time, body, optional image, vote arrows showing `score` with the user's own vote highlighted), right rail ("Top this month" game cards with download counts). Guests see feed but posting box replaced with sign-in CTA. No dead links — anything rendered works.

- [ ] **Step 5: Run tests to verify they pass**

```bash
docker run --rm -v "$PWD/gpn":/app -w /app php:8.4-cli php artisan test --filter=CommunityTest
```

Expected: PASS (5 tests).

- [ ] **Step 6: Commit**

```bash
git add -A && git commit -m "feat: community feed with voting and trending hashtags (SRS 4.1)"
```

---

### Task 12: Polish — error pages, animations, motion, Pint

**Files:**
- Create: `gpn/resources/views/errors/{403,404,419,500}.blade.php`, `gpn/resources/css/animations.css` (imported from `app.css`)
- Modify: `gpn/resources/views/auth/login.blade.php` (account-created overlay final), `gpn/resources/views/storefront/home.blade.php` (intro overlay final), `gpn/README.md`
- Test: `gpn/tests/Feature/ErrorPagesTest.php`, `gpn/tests/Feature/PolishTest.php`

**Interfaces:**
- Consumes: everything. Produces nothing new.

- [ ] **Step 1: Write the failing tests**

`gpn/tests/Feature/ErrorPagesTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\{Game, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_404_page_is_themed(): void
    {
        $this->get('/no-such-page')->assertNotFound()->assertSee('Page not found')->assertSee('GPlayNation');
    }

    public function test_403_page_is_themed(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden()->assertSee('Access denied');
    }

    public function test_expired_session_page_is_themed(): void
    {
        // token in session must DIFFER from posted token to trigger the CSRF mismatch
        $response = $this->withSession(['_token' => 'session-token'])->post('/login', [
            'email' => 'a@b.c', 'password' => 'x', '_token' => 'form-token',
        ]);
        $response->assertStatus(419)->assertSee('Session expired');
    }
}
```

`gpn/tests/Feature/PolishTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolishTest extends TestCase
{
    use RefreshDatabase;

    public function test_intro_animation_shows_once_per_session(): void
    {
        \App\Models\Game::factory()->published()->create();
        $this->get('/')->assertSee('id="intro-overlay"', escape: false);
        $this->get('/')->assertDontSee('id="intro-overlay"', escape: false);
    }

    public function test_account_created_overlay_renders_after_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'A', 'email' => 'a@b.cd', 'password' => 'secret123',
            'password_confirmation' => 'secret123', 'account_type' => 'user', 'terms' => 'on',
        ]);
        $this->get(route('home'))->assertSee('Account created');
    }

    public function test_no_macos_chrome_anywhere(): void
    {
        \App\Models\Game::factory()->published()->create();
        foreach (['/', '/store', '/community', '/login', '/register'] as $url) {
            $this->get($url)->assertDontSee('traffic')->assertDontSee('titlebar');
        }
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
docker run --rm -v "$PWD/gpn":/app -w /app php:8.4-cli php artisan test --filter="ErrorPagesTest|PolishTest"
```

Expected: FAIL (419 test may already behave — keep whichever assertion matches reality).

- [ ] **Step 3: Error pages + animations**

`errors/404.blade.php` (and siblings) — minimal standalone pages reusing tokens (no layout dependency): centered card, big status code, message ("Page not found" / "Access denied" / "Session expired" / "Something went wrong"), button back to `route('home')`. 403 page shows `{{ $exception->getMessage() }}` when non-empty.

`resources/css/animations.css` (add `@import './animations.css';` to `app.css` after the Tailwind import):

```css
@keyframes gpn-fade-scale { from { opacity: 0; transform: scale(.92); } to { opacity: 1; transform: scale(1); } }
@keyframes gpn-fade-out { to { opacity: 0; visibility: hidden; } }
@keyframes gpn-glow { 0%,100% { filter: drop-shadow(0 0 8px #1D4ED8); } 50% { filter: drop-shadow(0 0 22px #287BF7); } }

.overlay-enter { animation: gpn-fade-scale .45s ease-out both; }
.overlay-leave { animation: gpn-fade-out .5s ease-in 1.9s both; }
.logo-glow { animation: gpn-glow 1.4s ease-in-out infinite; }

@media (prefers-reduced-motion: reduce) {
    .overlay-enter, .overlay-leave, .logo-glow { animation: none !important; }
    #intro-overlay, #account-created { display: none !important; }
}
```

Intro overlay (home): full-screen `bg-bg z-50 fixed inset-0 grid place-items-center overlay-leave`, logo with `logo-glow` + wordmark `overlay-enter`, removed from DOM via `animationend` JS + click-to-skip. Account-created overlay (login + home): same pattern with check icon and the developer-variant copy.

Responsive pass (NF-04): portal sidebar becomes `hidden md:flex` with a compact top bar below `md`; grids collapse through `grid-cols-2 md:grid-cols-3 xl:grid-cols-5`; header search hides below `lg`; nothing forces horizontal scroll at ≥360px.

- [ ] **Step 4: README + Pint**

`gpn/README.md`: project intro, requirements (Docker), setup (composer install, cp .env, key:generate, migrate --seed, npm ci + build, storage:link, docker run serve), demo accounts (from Task 13), test command.

Run Pint and fix:

```bash
docker run --rm -v "$PWD/gpn":/app -w /app php:8.4-cli vendor/bin/pint
```

- [ ] **Step 5: Run full suite**

```bash
docker run --rm -v "$PWD/gpn":/app -w /app php:8.4-cli php artisan test
docker run --rm -v "$PWD/gpn":/app -w /app php:8.4-cli vendor/bin/pint --test
```

Expected: all PASS, Pint clean.

- [ ] **Step 6: Commit**

```bash
git add -A && git commit -m "feat: themed error pages, intro/account animations, motion-safe polish"
```

---
### Task 13: Seeders & demo world

**Files:**
- Create: `gpn/database/seeders/{UserSeeder,CatalogSeeder,TransactionSeeder,CommunitySeeder}.php`, `gpn/app/Support/CoverArt.php`
- Modify: `gpn/database/seeders/DatabaseSeeder.php`
- Test: `gpn/tests/Feature/SeederSmokeTest.php`

**Interfaces:**
- Consumes: all models, `CommissionService`, factories.
- Produces: demo accounts `admin@gplaynation.test`, `dev@gplaynation.test` (verified), `pending@gplaynation.test` (unverified dev), `user@gplaynation.test` — all password `password`; ~12 published games with SVG covers + real downloadable installer files.

- [ ] **Step 1: Write the failing test**

`gpn/tests/Feature/SeederSmokeTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\{Game, Transaksi, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SeederSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_world_renders_every_screen_for_every_role(): void
    {
        Storage::fake('public'); // seeder writes covers/installers — keep them off the real disk
        Artisan::call('db:seed');

        // guest screens
        foreach (['/', '/store', '/community', '/login', '/register'] as $url) {
            $this->get($url)->assertOk();
        }

        // first published game detail + auth screens
        $game = Game::published()->firstOrFail();
        $this->get("/store/{$game->slug}")->assertOk();

        $user = User::where('email', 'user@gplaynation.test')->firstOrFail();
        foreach (['/profile', '/transactions'] as $url) {
            $this->actingAs($user)->get($url)->assertOk();
        }

        $dev = User::where('email', 'dev@gplaynation.test')->firstOrFail();
        foreach (['/dev', '/dev/games', '/dev/games/create', '/dev/sales', '/dev/promotion', '/dev/tags', '/dev/settings', '/dev/payouts'] as $url) {
            $this->actingAs($dev)->get($url)->assertOk();
        }

        $admin = User::where('email', 'admin@gplaynation.test')->firstOrFail();
        foreach (['/admin', '/admin/verification', '/admin/moderation', '/admin/commission', '/admin/payouts', '/admin/reports'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }

        $this->assertGreaterThanOrEqual(12, Game::published()->count());
        $this->assertGreaterThan(30, Transaksi::count());
    }

    public function test_seeded_game_files_exist_on_disk(): void
    {
        Storage::fake('public');
        Artisan::call('db:seed');
        $game = Game::published()->firstOrFail();
        $this->assertTrue(Storage::disk('public')->exists($game->cover_path));
        $this->assertTrue(Storage::disk('public')->exists($game->file_path));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
docker run --rm -v "$PWD/gpn":/app -w /app php:8.4-cli php artisan test --filter=SeederSmokeTest
```

Expected: FAIL.

- [ ] **Step 3: Cover art generator + seeders**

`app/Support/CoverArt.php` — deterministic SVG cover per title (hash → hue pair, gradient + big initials + subtle grid), plus `installer()` returning a small zip placeholder:

```php
<?php

namespace App\Support;

class CoverArt
{
    public static function svg(string $title): string
    {
        $h = crc32($title);
        $hue1 = $h % 360;
        $hue2 = ($hue1 + 40) % 360;
        $initials = mb_strtoupper(collect(explode(' ', $title))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode(''));

        return <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 360">
          <defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="hsl($hue1,70%,38%)"/><stop offset="100%" stop-color="hsl($hue2,80%,22%)"/>
          </linearGradient></defs>
          <rect width="640" height="360" fill="url(#g)"/>
          <text x="50%" y="54%" text-anchor="middle" font-family="Inter,Arial" font-size="120" font-weight="600" fill="rgba(255,255,255,.92)">$initials</text>
        </svg>
        SVG;
    }
}
```

`UserSeeder`: admin, verified dev (Studio Nusantara), second verified dev (Arus Digital), pending dev, rejected dev, 3 users — fixed emails, `password`.

`CatalogSeeder`: 12 Indonesian-local-flavored games (e.g. "Pocong Runner", "Keris of Majapahit", "Warung Tycoon", "Hantu Laut", "Batik Heroes", "Garuda Strike", "Sawah Simulator", "Kuntilanak Mansion", "Angkot Rush", "Candi Puzzle", "Lorong Waktu", "Sate Bandits") split across both verified devs and the 10 genres; 3 free, 1 with an active promotion, 1 `pending_review`, 1 `rejected` (with feedback), 1 `archived`; each: SVG cover stored (`covers/{slug}.svg`), 2 screenshots (same generator, different seeds), downloadable installer `installers/{slug}.zip` — a text README payload stored under a `.zip` name (**no `ZipArchive`**: the `php:8.4-cli` image lacks ext-zip), 2–4 tags each, 0–3 IAPs on paid games. One game gets an active promotion (30% off).

`TransactionSeeder`: for the past ~90 days, 40–70 settled transactions spread across games/users — for each, create `Transaksi` (status success, created_at = paid time), `Pembayaran` (random method, `paid_at`), `KomisiTransaksi` via `CommissionService::snapshot()` **but with the trx's own created date** — acceptable: rate lookups use `now()`; to keep period math honest, compute snapshot manually with `percentageFor($trx->created_at)`. Also a few `pending`/`failed`/`expired` rows. A handful of `Library` + `Download` rows for realism. Seed `Komisi` schedule: 20% effective 2026-01-01 by admin.

`CommunitySeeder`: ~15 posts from users with hashtags (`#gamelokal`, `#horrorid`, `#indie`), votes spread.

`DatabaseSeeder` calls the four seeders in order.

- [ ] **Step 4: Run tests to verify they pass**

```bash
docker run --rm -v "$PWD/gpn":/app -w /app php:8.4-cli php artisan test --filter=SeederSmokeTest
```

Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat: seeded demo world — users, catalog, transactions, community"
```

---

### Task 14: Docker redeploy & final verification

**Files:**
- Modify: `gpn/README.md` (final run instructions)
- Recreate: Docker container `gpn-serve`

**Interfaces:**
- Consumes: `docker/php-uploads.ini` (Task 1), everything.

- [ ] **Step 1: Build production assets & install deps**

```bash
cd /home/sudomb/Dev/GPlayNation
npm --prefix gpn run build
docker run --rm -v "$PWD/gpn":/app -w /app composer install --optimize-autoloader
```

(Dev deps stay installed in the demo container — `phpunit` present is fine.)

- [ ] **Step 2: Prepare runtime**

```bash
docker run --rm -v "$PWD/gpn":/app -w /app php:8.4-cli php artisan storage:link
ls gpn/database/database.sqlite || touch gpn/database/database.sqlite
```

- [ ] **Step 3: Recreate the container**

```bash
docker rm -f gpn-serve 2>/dev/null || true
docker run -d --name gpn-serve --restart unless-stopped \
  -p 8000:8000 \
  -v /home/sudomb/Dev/GPlayNation/gpn:/app \
  -v /home/sudomb/Dev/GPlayNation/docker/php-uploads.ini:/usr/local/etc/php/conf.d/uploads.ini:ro \
  -w /app \
  php:8.4-cli php artisan serve --host=0.0.0.0 --port=8000
```

- [ ] **Step 4: Migrate + seed inside the container**

```bash
docker exec gpn-serve php artisan migrate:fresh --seed --force
```

Expected: migrations run, seeders complete.

- [ ] **Step 5: HTTP smoke check through the container**

```bash
sleep 2
curl -s -o /dev/null -w "%{http_code} " http://localhost:8000/        # 200
curl -s -o /dev/null -w "%{http_code} " http://localhost:8000/store   # 200
curl -s -o /dev/null -w "%{http_code} " http://localhost:8000/login   # 200
curl -s -o /dev/null -w "%{http_code} " http://localhost:8000/dev     # 302 (guest redirect)
curl -s http://localhost:8000/ | grep -ci "titlebar\|traffic"          # 0
docker exec gpn-serve sh -c 'test ! -s storage/logs/laravel.log || wc -l storage/logs/laravel.log'
```

Expected: `200 200 200 302`, grep count `0`, log empty (or only notices).

- [ ] **Step 6: Final gates**

```bash
docker run --rm -v "$PWD/gpn":/app -w /app php:8.4-cli php artisan test
docker run --rm -v "$PWD/gpn":/app -w /app php:8.4-cli vendor/bin/pint --test
npm --prefix gpn run build
```

Expected: suite green, Pint clean, build clean — matching spec §13 acceptance criteria 1–9.

- [ ] **Step 7: Final commit**

```bash
git add -A && git commit -m "chore: docker redeploy, final verification (spec §13)"
```

---

## Self-Review Notes (completed by plan author)

- **Spec coverage:** §4 data model → Task 3; §5 auth → Task 4; §6 storefront → Tasks 5–6; §7 checkout/payment → Task 7; §8 dev portal → Tasks 8–9; §9 admin → Task 10; §10 design system → Tasks 2, 5–12 views; §11 integrity → Tasks 7–10 services + constraints; §12 testing → per-task test files; §13 acceptance → Tasks 12–14; Docker redeploy requirement → Task 14. Deviation noted: spec §4.1 `libraries` gained `is_showcased` (SRS §4.1 showcase feature) — spec amended alongside this plan.
- **Type consistency:** `Money::format(string)` returns `Rp …`; `effectivePrice()`/`unpaidBalance()`/KPI values are decimal **strings** — views always pass them through `Money::format` (never format twice); `ReportService` returns arrays shaped exactly as consumed in Tasks 8–10; `handleNotification(array): Transaksi` used by both HTTP webhook and in-process simulator.
- **Placeholder scan:** views for Tasks 5–11 are specified structurally with exact bindings/routes/components (executors read spec + `DESIGN-TOKENS.md` for pixel detail); all logic code is complete.

