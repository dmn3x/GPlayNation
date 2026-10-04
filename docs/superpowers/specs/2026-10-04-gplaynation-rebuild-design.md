# GPlayNation Rebuild — Design Spec

**Date:** 2026-10-04
**Status:** Approved design, pending implementation plan
**Sources:** `GPlayNation SRS.docx.pdf` (57 pp., Indonesian), Figma file `GPlayNation` node `358:2` (12 screens + hover/animation states), `DESIGN-TOKENS.md`, exploration of the legacy prototype (`gpn/`, to be archived).

---

## 1. Purpose

Rebuild GPlayNation — a web marketplace where Indonesian local game developers distribute and sell games and in-app purchases (IAP) to users, with admin-managed verification, moderation, commission, payout, and reporting — from scratch, to production quality: polished, complete, and error-free.

The previous prototype (`gpn/`, built 2026-10-03/04) covers ~75% of SRS breadth at demo depth: stubbed OAuth (account-takeover hole), fake Midtrans token, sine-curve chart, random comment counts, a live `TypeError` in checkout, a moderation-bypass hole, missing edit flows, and dead tables. It is archived to `gpn-legacy/` and nothing is inherited by file — only its proven *ideas* (signed-webhook settlement pipeline, feature-test scenarios) are re-implemented cleanly.

## 2. Decisions log

| # | Decision | Choice |
|---|---|---|
| D1 | Rebuild scope | Fresh rebuild in place; legacy moved to `gpn-legacy/`; port webhook flow + test scenarios as ideas only |
| D2 | Integrations | **Simulated only** — no external calls. Built-in payment gateway simulator; no OAuth buttons (email+password auth only) |
| D3 | Admin panel | Built as full UI in the Figma dark design language (SRS requires it; Figma omits it) |
| D4 | Architecture | Laravel 12 + Blade + Tailwind CSS 4 (Vite); vanilla-JS islands only where the design is interactive |
| D5 | Figma window chrome | The macOS title bar is canvas decoration and is **not implemented**; the app renders full-viewport, no rounded "window" shell |
| D6 | Database (dev/test) | SQLite via the existing PHP 8.4 Docker container; schema remains MySQL-8-compatible (SRS production target); no SQLite-only SQL |
| D7 | UI language | English, per the Figma screens (SRS text is Indonesian) |
| D8 | Deviation from SRS Kamus Data | `laporans` table **dropped** (reports computed live); developer attributes (`namaStudio`, `developerPortofolio`) live on `developers`, not `admins` (SRS dictionary typo) |

SRS grounding: F-01…F-10 (§2.5), NF-01…NF-06, use cases 3.1.1.1–3.1.5.5, activity diagrams 3-2.1…3-2.6, interface §4.1 (login/register, profile, community, store).

## 3. Architecture

### 3.1 Stack

- **Backend:** Laravel 12, PHP ^8.2 (container runs 8.4). Hand-rolled auth (no Breeze/Jetstream), Eloquent, FormRequests, Blade.
- **Frontend:** Blade templates, Tailwind CSS 4 via `@tailwindcss/vite`, vanilla-JS islands: area-chart renderer, gateway simulator modal/page, toast dismiss, intro animation, tabs/accordion. No JS framework, no chart library.
- **DB:** SQLite file `database/database.sqlite` (dev), in-memory SQLite (tests). Session/cache/queue: database driver.
- **Storage:** `public` disk + `storage:link` for covers, screenshots, avatars, installers, post images. (SRS's S3 is a production concern; local disk here.)
- **Payments:** simulated gateway (§8). No external HTTP calls anywhere in the app.
- **Fonts:** Inter self-hosted via `@fontsource/inter` (no CDN).

### 3.2 Environment & deployment

- Dev served from Docker container `gpn-serve` (`php:8.4-cli`, bind-mount project → `/app`, `php artisan serve --host=0.0.0.0 --port=8000`).
- **Deployment requirement (user, 2026-10-04):** at handoff the finished app must be running in Docker and reachable in the user's browser. The existing `gpn-serve` container is recreated against the rebuilt app (same `php:8.4-cli` image, same mount/port) with the ini overrides below baked in; final verification includes an HTTP 200 smoke check through the container.
- PHP ini raised for installer uploads: `upload_max_filesize=64M`, `post_max_size=64M` (mounted conf snippet in the recreated container).
- Composer runs via the `composer` Docker image against the same mount (host has no PHP/Composer).
- Node 24 on host for `npm ci` / `npm run build` (Vite). Assets committed as built output is *not* required; build is part of setup.

### 3.3 Code organization (targets)

```
gpn/
  app/
    Http/Controllers/{Auth,Storefront,Checkout,Gateway,Community,Profile,Developer,Admin}...
    Http/Requests/...            # one FormRequest per write flow
    Http/Middleware/EnsureRole.php
    Models/...
    Services/Payment/{PaymentGateway.php, SimulatedGateway.php, Signature.php}
    Services/{CommissionService.php, PayoutService.php, ReportService.php}
    Support/Money.php            # Rp formatting, id-ID thousands
  resources/views/
    layouts/{app,portal}.blade.php      # storefront shell, sidebar shell (dev+admin)
    components/{ui,icon,...}
    storefront/{home,store,game,community,profile,transactions}...
    checkout/... gateway/...
    developer/{dashboard,games,sales,promotion,tags,settings,payouts}...
    admin/{dashboard,verification,moderation,commission,payouts,reports}...
    errors/{403,404,419,500}.blade.php
  tests/Feature/...              # §11
  database/{migrations,seeders,factories}
```

Layers: controllers are thin; settlement/commission/payout/report math lives in `app/Services`; models carry relations + scopes only.

## 4. Data model

Domain tables keep SRS Kamus Data names (`transaksis`, `pembayarans`, `komisis`) for SRS traceability; support tables use Laravel conventions. All money `decimal(12,2)` IDR; displayed `Rp 1.500.000` (id-ID grouping, no decimals). All timestamps UTC.

### 4.1 Tables

**`users`** — `id`, `name` varchar(100), `email` varchar(150) unique, `password`, `role` enum(`user`,`developer`,`admin`), `status` enum(`active`,`pending`,`rejected`) default by role (user→active, developer→pending), `avatar_path` nullable, `rejection_reason` text nullable, `remember_token`, timestamps.

**`developers`** — `id`, `user_id` FK→users unique, `studio_name` varchar(120), `portfolio` text nullable, `payout_bank` varchar(100) nullable, `payout_account` varchar(100) nullable, timestamps.

**`games`** — `id`, `developer_id` FK, `name` varchar(150), `slug` unique, `description` text, `price` decimal(12,2) (0 = free), `genre` varchar(50), `cover_path`, `file_path` (installer), `trailer_url` nullable, `status` enum(`draft`,`pending_review`,`published`,`rejected`,`archived`), `rejection_feedback` text nullable, timestamps. Indexes: `(status, genre)`, `slug` unique.

**`game_screenshots`** — `id`, `game_id` FK cascade, `path`, `sort` smallint.

**`tags`** — `id`, `name` varchar(50) unique. **`game_tag`** — `game_id` FK, `tag_id` FK, unique(`game_id`,`tag_id`).

**`iaps`** — `id`, `game_id` FK cascade, `name` varchar(150), `description` text nullable, `price` decimal(12,2), `type` enum(`currency`,`item`,`upgrade`,`cosmetic`), `is_active` bool default true, timestamps.

**`promotions`** — `id`, `game_id` FK cascade, `discount_percent` tinyint (1–90), `starts_at` datetime, `ends_at` datetime, timestamps. At most one *active* promotion per game enforced in the service layer (overlap validation).

**`transaksis`** — `id`, `order_id` varchar(30) unique (`GPN-YYYYMMDD-XXXXXX`), `user_id` FK, `game_id` FK (always set; parent game for IAP purchases), `iap_id` FK nullable (null = base-game purchase), `type` enum(`game`,`iap`), `total` decimal(12,2) (charged amount after promotion), `status` enum(`pending`,`success`,`failed`,`expired`), timestamps. Double purchase is prevented by the `libraries`/`iap_ownerships` unique keys plus a pre-check (§11).

**`pembayarans`** — `id`, `transaksi_id` FK, `method` enum(`e_wallet`,`virtual_account`,`credit_card`), `amount` decimal(12,2), `reference_no` varchar(100), `payload` json (raw simulated notification), `paid_at` nullable, timestamps.

**`komisis`** — `id`, `admin_id` FK→users, `percentage` decimal(5,2) (0–50), `effective_from` date, timestamps. Append-only; never updated or deleted.

**`komisi_transaksi`** — `id`, `transaksi_id` FK unique, `komisi_id` FK, `percentage` decimal(5,2) snapshot, `amount` decimal(12,2) (platform cut), `developer_amount` decimal(12,2) (net), `payout_id` FK nullable (set when paid out), created timestamps.

**`payouts`** — `id`, `developer_id` FK, `admin_id` FK→users, `period` varchar(7) (`YYYY-MM` of processing), `gross`, `commission`, `net` (sums of covered `komisi_transaksi` rows), `status` enum(`processed`) — rows exist only once processed, timestamps.

**`libraries`** — `id`, `user_id` FK, `game_id` FK, `transaksi_id` FK nullable (null = free claim), `source` enum(`purchase`,`free`), created timestamps. Unique(`user_id`,`game_id`).

**`iap_ownerships`** — `id`, `user_id` FK, `iap_id` FK, `transaksi_id` FK, created timestamps. Unique(`user_id`,`iap_id`).

**`wishlists`** — `id`, `user_id` FK, `game_id` FK, timestamps. Unique(`user_id`,`game_id`).

**`posts`** — `id`, `user_id` FK, `body` text (max 2000), `image_path` nullable, `hashtags` json (parsed lowercased), timestamps.

**`post_votes`** — `id`, `post_id` FK cascade, `user_id` FK, `value` tinyint (−1|+1), timestamps. Unique(`post_id`,`user_id`).

**`downloads`** — `id`, `user_id` FK, `game_id` FK, created timestamp only (append-only activity log, F-09 download statistics).

Stock Laravel tables (sessions, cache, jobs, password_reset_tokens) retained as-is.

### 4.2 State machines

- **Game:** `draft → pending_review → published | rejected`; `rejected → pending_review` (resubmit); `published ↔ archived` (availability toggle, 3.1.2.6); **any metadata/file edit to a `published` game → `pending_review`** (closes legacy moderation bypass). Price change and archive/unarchive apply instantly, no re-moderation.
- **Transaksi:** `pending → success | failed | expired`. Terminal states immutable (idempotency).
- **User (developer):** `pending → active | rejected`; `rejected` keeps reason, may log in only after re-verification — rejected developers **cannot** log in (login denied with reason shown), may re-register with a different email. Admin accounts seeded only.
- **Post vote:** toggle semantics — same vote removes it; opposite vote flips it.

### 4.3 Commission & payout math

- **Rate lookup** (settlement time): latest `komisis` row with `effective_from <= settlement date`, tie-break highest `id`. If none exists, fall back to the most recent row; if the table is empty, platform default `20%` (constant, used by seeders to create the initial schedule row).
- **Settlement snapshot:** `amount = round(total × pct/100, 2)`; `developer_amount = total − amount`. Written once into `komisi_transaksi` with the `komisi_id` used.
- **Unpaid developer balance:** `sum(komisi_transaksi.developer_amount where payout_id is null)` over that developer's settled transactions.
- **Processing a payout** (3.1.4.3): one `DB::transaction` — select all unpaid `komisi_transaksi` rows for the developer, create `payouts` row (gross = Σ transaksi totals, commission = Σ amount, net = Σ developer_amount, period = current `YYYY-MM`), stamp `payout_id` on the covered rows. Empty set → message "payout belum dapat diproses" (SRS alternate flow).

### 4.4 Promotion pricing

Effective price of a game = if a promotion row exists with `starts_at <= now <= ends_at` → `round(price × (100 − discount_percent)/100, 2)`; else `price`. Applied at checkout creation (stored in `transaksis.total`), displayed in store/detail with strikethrough original. Promotions never apply to IAPs.

## 5. Auth & roles

- **Register** (`/register`): account type toggle **Pengguna | Developer** (3.1.1.1). Fields: name, email, password+confirmation, terms checkbox; developers additionally `studio_name` (required), `portfolio` (optional). User → `active`, logged in immediately. Developer → `pending`, **not** logged in, redirected to login with "menunggu verifikasi" notice. Duplicate email → inline error (SRS alternate flow). Success plays the account-created animation (Figma "Account Created Animation") once, then the login form.
- **Login** (`/login`): email+password, throttled (5/min). Pending developer → login refused, "akun masih menunggu verifikasi" (3.1.1.2 alternate flow). Rejected developer → refused with the admin's reason. Success → role redirect: `admin→/admin`, `developer→/dev`, `user→/`.
- **Middleware:** `role:developer`, `role:admin` (`EnsureRole`) on the respective prefixes; developer area additionally requires `status=active`.
- **Profile** (`/profile`, all roles): view/edit name, avatar, password change (current-password check), cancel discards (3.1.1.3). Admin accounts are seeded (`admin@gplaynation.test`), never self-registered.

## 6. Storefront (public + user)

Layout `layouts/app`: header per Figma Home — logo, nav (Home, Store, Community), search box, right side: Sign in (guest) or avatar menu (Profile, Transactions, Dev portal if developer, Admin if admin, Logout). Footer minimal. **No macOS title bar.**

| Route | Screen (Figma) | Behavior |
|---|---|---|
| `GET /` | Home `6:1427` | First visit per session: intro animation (Loading Open App frames — logo reveal ~1.2 s, skippable, respects `prefers-reduced-motion`, session flag). Hero = the published game with the most recently started active promotion; if none, the most-downloaded published game; rows: Popular (by downloads), New Releases (latest published), each ≤10 cards. |
| `GET /store` | Store `6:1698` | Sidebar: keyword search, genre list, tags, price bands — Free (=0) / Under Rp 50rb (>0, <50 000) / Rp 50–150rb (50 000–150 000 inclusive) / Above Rp 150rb (>150 000). Sort: newest, price ↑↓, name. Only `published`. Empty → "game tidak ditemukan" state (3.1.2.1 alternate). Pagination keeps query string. |
| `GET /store/{game:slug}` | Game `6:1559` | Cover, screenshots, trailer link, description, price/effective price, tags, IAP list (active only) with per-item Buy. CTA: Free → Download (claims to library); owned → Download; else Buy. Wishlist toggle (auth). Unavailable → 404-themed message (3.1.2.2 alternate). |
| `GET /community` | Community `181:529` | Hero, trending-hashtag sidebar (real counts last 7 days), feed newest-first with author, time, real vote counts, ▲▼ voting (auth, toggles), create-post box (auth, body + optional image, `#tags` parsed). Right rail: top downloaded games this month. Every control functional; nothing decorative-but-dead. |
| `GET /profile` | Profile `6:1890` | Stats: games owned, wishlist count, IAP owned. Library grid with per-game "showcase" toggle controlling what displays on the profile's main section (SRS §4.1). Edit-profile form. |
| `GET /transactions` | (app shell) | List (date, item, nominal, status) + detail per transaction: method, reference no., status timeline (3.1.3.4). Empty state per SRS alternate flow. |
| `GET /games/{game:slug}/download` | — | Auth; ownership check (library row or free game) else redirect to purchase (3.1.2.3 alternate); logs `downloads` row; streams installer. |

## 7. Checkout & payment simulation

### 7.1 Purchase flow (3.1.3.1/3.1.3.2, activity 3-2.2)

1. `POST /checkout/{game|iap}/{id}` (auth) → validations: game published; not already owned (game→`libraries`, iap→`iap_ownerships`); IAP's parent game owned or in same cart-free rule — **IAP purchase requires owning the base game** (library row) *or* the base game being free; creates `transaksis` (`pending`, unique `order_id`, `total` = effective price) → redirect to summary.
2. `GET /checkout/{order_id}` — order summary + terms-of-sale checkbox; **Pay** button.
3. `POST /checkout/{order_id}/pay` → redirects to the simulator. User cancel before paying → transaction stays `pending` (retryable from the summary; expiry cleans it up) and nothing is granted. Mapping note: user-cancel keeps `pending`; gateway `cancel` maps to `failed` (§7.3).
4. Double-submit safe: unique `order_id`, ownership unique keys, settlement idempotency (§9).

### 7.2 Gateway simulator (D2)

- `GET /gateway/{order_id}` — page styled like a Midtrans Snap popup (per activity 3-2.2: E-Wallet / Virtual Account / Credit Card choice), **explicitly labeled** "SIMULATED PAYMENT — GPlayNation Dev Gateway". Choosing a method shows method-specific fake instrument (e.g., VA number) and **Pay / Fail / Expire** buttons (deterministic demo control).
- On choice, the simulator controller builds a Midtrans-shaped notification (`order_id`, `transaction_status`, `gross_amount`, `status_code`, `payment_type`, `signature_key`) where `signature_key = hash('sha512', order_id . status_code . gross_amount . server_key)` and **dispatches it in-process to the webhook handler** — the exact code path an HTTP callback takes, signature verification included. (In-process dispatch avoids PHP dev-server self-request deadlock; the HTTP route exists and is covered by tests.)
- Result redirect: `GET /checkout/{order_id}/result` → themed success / failed / expired page from DB state; success links to Profile library / download.

### 7.3 Webhook & settlement (activity 3-2.4, 3.1.3.3)

`POST /webhooks/payment` (CSRF-exempt):

1. `server_key` missing → **503 fail-closed**. Signature mismatch → 403 + exception log. Unknown order → 404.
2. Already-terminal transaction → 200 (idempotent replay).
3. Status mapping: `settlement`/`capture` → `success`; `pending` → stays `pending`; `deny`/`cancel` → `failed`; `expire` → `expired`.
4. On `success`, one `DB::transaction` (NF-05 atomicity):
   - update `transaksis.status`, insert `pembayarans` row (method, amount, generated `reference_no`, raw payload, `paid_at`);
   - grant ownership: game purchase → `libraries` (source `purchase`); IAP → `iap_ownerships` (never grants the base game — fixes legacy bug);
   - commission snapshot via `CommissionService` into `komisi_transaksi`.
5. `php artisan transactions:expire` marks stale `pending` (> `config('gpn.transaction_ttl_hours')`, default 24) as `expired`; scheduled daily.

## 8. Developer portal (`/dev`, verified developers only; Figma Dev Mode)

Layout `layouts/portal`: dark sidebar (logo, "Developer Tools", nav, user block) exactly in the Figma Dev-Mode language.

| Route | Screen | Behavior |
|---|---|---|
| `GET /dev` | Dashboard `6:1976` | Real counts: lifetime gross, net, sales count, downloads; recent 10 settled sales; per-game status list. Area chart: daily settled revenue for selected month, bucketed on `pembayarans.paid_at` (settlement timestamp, portable `date()` SQL) joined to `transaksis`, SVG per token spec. Empty data → honest empty state, zero-filled series. |
| `GET/POST /dev/games` | (portal shell) | List own games with statuses + rejection feedback. Upload form (F-07): name, description, genre (fixed list: Action, Adventure, RPG, Strategy, Simulation, Puzzle, Horror, Racing, Sports, Casual), price, cover (required, image ≤2 MB), screenshots (≤5, ≤2 MB each), trailer URL (optional), installer file (required, ≤50 MB). Submit → validation (format/size errors → inline message per 3.1.2.4) → `pending_review` + confirmation (3-2.1). |
| `GET/PUT /dev/games/{game}` | — | Edit own games only (403 cross-developer). Editing metadata/files of a `published` game → back to `pending_review` with notice. Price-only change instant. Archive/unarchive toggle instant (3.1.2.6). |
| `POST /dev/games/{game}/iaps`, `PUT /dev/iaps/{iap}` | — | IAP create/edit/deactivate (3.1.2.5-IAP): name, description, price, type, active flag. |
| `GET /dev/sales` | Sales `161:138` | Period (month) + per-game filters; KPI cards (gross, commission, net, units, downloads); area chart; settled-transactions table with pagination keeping filters; per-period empty state (3.1.5.1 alternate). |
| `GET/POST /dev/promotion` | Promotion `74:46` | Create promotion for own published game: percent 1–90, start/end datetime (end > start, no overlap with another active promotion on same game). List with status (scheduled/active/ended), delete scheduled ones. |
| `GET/POST /dev/tags` | Tags `74:212` | Create tags, attach/detach to own games. |
| `GET/POST /dev/settings` | Setting `142:216` | Studio name, portfolio, payout bank/account. |
| `GET /dev/payouts` | (portal shell) | Unpaid balance (per §4.3), payout history (period, gross, commission, net, date). |

## 9. Admin panel (`/admin`, Figma dark language, new screens)

| Route | Behavior |
|---|---|
| `GET /admin` | Counts: pending developers, pending games, users, today's transactions, commission revenue this month. |
| `GET /admin/verification` · `POST /admin/verification/{user}` | Pending developer queue → detail → **Approve** (status→active) / **Reject** (reason required, 3.1.1.4). |
| `GET /admin/moderation` · `GET /admin/moderation/{game}` · `POST ...` | `pending_review` queue (3-2.3): full game detail incl. media + file size → **Approve** (→`published`, appears in catalog) / **Reject** (feedback required → `rejected`, feedback shown to developer). |
| `GET/POST /admin/commission` | Current rate display; new schedule row: percentage (0–50), effective_from (date); append-only history list (3.1.4.2). |
| `GET /admin/payouts` · `POST /admin/payouts/{developer}` | Per-developer unpaid balances table; **Process Payout** (§4.3, 3.1.4.3); payout history (3.1.5.5); empty-balance message per SRS. |
| `GET /admin/reports` | Tabs: Transactions (period + status filters; totals row) and Commission (period filter; per-developer breakdown) — F-10, 3.1.5.3/3.1.5.4. Filters persist through pagination (`withQueryString`). Empty → SRS "data tidak ditemukan" state. |

## 10. Design system & UI polish

- **Tokens** (`DESIGN-TOKENS.md` → CSS custom properties + Tailwind theme): surfaces `#0F0F10 / #161618 / #222224`, borders `#222224 / #27282A`, text white @100/60/50/40/30, `--fg-blue #60A5FA`, danger `#E7000B`, success `#30D158`, accent `#1D4ED8` + `-hi #287BF7` + 10/15% alphas + focus ring; radii 4/8/10/12/16/9999; shadows `--shadow-card`, `--shadow-pop`, `--glow-accent(-lg)`; Inter type scale 27/18.9/16.2 per tokens. Traffic-dot tokens exist only in the token file — **no traffic dots or title bar in the app** (D5).
- **Hover/focus:** Figma hover sections become real states — nav items get `--accent-10` pill + white text; buttons darken/raise; cards lift (`--shadow-card` + 2% scale cover); `:focus-visible` gets the 1.67px `--accent-border` ring. All interactive elements reachable by keyboard.
- **Animations:** intro (one-time/session), account-created (one-time), login button loading state (per Login Animation frames: spinner → success → redirect). CSS keyframes, `prefers-reduced-motion` honored, none block content.
- **Chart:** hand-rolled SVG area chart — 1.5px `--accent` line, `--accent-15`→transparent fill, grid lines `--border`, month labels from real data.
- **Icons & logo:** single inline-SVG icon component set (store, community, profile, search, download, wallet, chart, tag, settings, logout, check, x, chevrons, plus, edit, trash, upload, vote arrows); GPlayNation logo redrawn as inline SVG from the Figma vector (`5:88`).
- **Feedback:** session-flash toasts (success/error, auto-dismiss, closable) for every write op per SRS "dialog box" requirements; form errors inline under fields; themed `403/404/419/500` pages.
- **Responsive:** NF-04 — layouts hold from 1280px up; below that, sidebars collapse into top bars; no horizontal scroll ≥360px.

## 11. Error handling & data integrity

- One FormRequest per write flow (register, login, profile, game create/update, IAP, promotion, tag, commission, payout process, post, vote, checkout). Errors inline, old input preserved.
- `DB::transaction` around: settlement, payout processing, free-claim, ownership grants. Unique constraints (`libraries`, `iap_ownerships`, `wishlists`, `post_votes`, `order_id`) are the final guard; violations map to friendly 409-style messages, never 500s.
- Double purchase: pre-check + race-safe unique key → message "already owned", redirect to library.
- Uploads validated (mime/extension/size) then stored on `public` disk; downloads via ownership-checked streamed response.
- Webhook: fail-closed without key, 403 on bad signature, idempotent on replay.
- Logging: payment exceptions and moderation/verification actions to `laravel.log`.
- Acceptance bar (§13): green test suite, Pint clean, zero log errors on full smoke pass.

## 12. Testing strategy

PHPUnit feature tests (in-memory SQLite), one file per area, ported + expanded from the legacy suite's ~29 scenarios:

- **AccountTest** — 3.1.1.1 register user (active, logged in), register developer (pending, blocked from login with message), duplicate email, login wrong password, role redirects, 3.1.1.4 approve/reject-with-reason flows, rejected developer sees reason, 3.1.1.3 profile update + cancel.
- **StorefrontTest** — 3.1.2.1 search/filter/sort + empty state, published-only visibility, 3.1.2.2 detail + unavailable, wishlist toggle, 3.1.2.3 free download claims library + logs download, paid download blocked until owned.
- **GameManagementTest** — 3.1.2.4 upload → `pending_review`, invalid file rejected, 3.1.2.5 update flows, published-edit → re-moderation, cross-developer edit 403, price/archive instant, 3.1.2.6 manage products, IAP CRUD.
- **CheckoutTest** — 3.1.3.1 game purchase summary → simulator → success result, 3.1.3.2 IAP purchase grants IAP **not** base game, double purchase blocked, cancel leaves pending, agreement required, IAP requires base-game ownership.
- **PaymentWebhookTest** — 3.1.3.3 + 3-2.4: valid signature settles (transaction+payment+library+commission snapshot in one transaction), forged signature 403, missing key 503, replay idempotent, pending/deny/expire mappings, unknown order 404, `transactions:expire`.
- **CommissionPayoutTest** — 3.1.4.2 schedule append-only + effective-date boundary (rate in force at settlement is used), 3.1.4.3 payout sums unpaid rows, stamps `payout_id`, empty → message, balance math after multiple payouts.
- **ReportsTest** — 3.1.5.1/2 developer sales+revenue periods and empty states, 3.1.5.3/4/5 admin transaction/commission/payout filters, pagination keeps filters.
- **CommunityTest** — post create (+hashtag parse, image), vote toggle/flip/remove, trending counts real, guest blocked from posting.
- **PromotionTest** — effective price on store/detail/checkout, expiry boundary, overlap rejected.
- **SmokeTest** — every GET route for each role returns 200/302 (no 500s), seeded demo data renders all screens.

Target: **all green**; `vendor/bin/pint --test` clean.

## 13. Acceptance criteria ("perfect, polished, no errors")

1. `php artisan test` — 100% pass. 2. `vendor/bin/pint --test` — clean. 3. `npm run build` — clean. 4. All 12 Figma screens implemented to tokens, **no macOS title bar anywhere**; admin panel visually consistent with Dev Mode. 5. Zero fake/stub/dead UI — every control works or doesn't exist (no `random_int` counts, no sine charts, no `#` links). 6. Full click-through smoke (guest→register→buy→download; developer→upload→moderated→sale→payout; admin→verify/moderate/commission/report) with an empty `laravel.log`. 7. `README.md` — setup/run instructions (Docker, migrate --seed, build, serve). 8. **The app is deployed and running in Docker** (`gpn-serve` recreated against the rebuilt app, port 8000) with an HTTP 200 smoke check on `/`, `/store`, `/login`, and the portal redirects. 9. Seeded demo world: admin, 1 pending + 2 verified developers, 3 users, 12 Indonesian local games (mixed free/paid/promoted) with generated SVG cover art, IAPs, tags, 3 months of transactions, community posts.

## 14. Explicit non-goals

Real Midtrans API, OAuth (Google/Apple), MySQL/Nginx/S3/Cloudflare deployment (SRS production topology only), CSV/PDF report export, email sending, password reset flow, mobile-native app, `laporans` rollup table.
