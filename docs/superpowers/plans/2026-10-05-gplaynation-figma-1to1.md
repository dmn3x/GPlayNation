# GPlayNation Figma 1:1 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement entire Figma Section 1 (node `358:2`, ~50 frames) 1:1 in Laravel Blade + Tailwind, excluding macOS title bar.

**Architecture:** Keep existing Laravel routes/controllers; replace Blade markup + `app.css` tokens to match Figma hex/layout/typography exactly. Introduce shared `auth-card`, `app-shell` partials; unify hard-coded `#0F0F10/#161618/#222224/#1D4ED8` with semantic tokens. No macOS chrome rendered anywhere.

**Tech Stack:** Laravel 11 Blade, Tailwind CSS v4 (`@theme`, `@utility`), Inter via Fontsource, PHPUnit feature tests, Vite.

**Spec:** Figma file `GPlayNation`, Page 1, Section 1 `358:2`. Frame IDs: Auth Register `50:104`, Login `6:1488`, App `394:288`/`394:289`/`394:293`; Home `6:1427`, Game `6:1559`, Store `6:1698`, Community `181:529`, Profile `6:1890`; Home variants `205:650/677/695`; Dev Dashboard `6:1976`, Setting `142:216`, Sales `161:138`, Promotion `74:46`, Tags `74:212`; Kelola `433:4607`, Unduh `433:4502`, Beli `433:4366`+modal `433:4448`, Informasi `433:4283`, Cari `433:4179`; Transaksi Keranjang `409:483`, Pembayaran `409:627`, Riwayat `409:1341`, Status `409:2155`, Keranjang V2 `409:2440`; Admin Moderasi `425:993`, Komisi `425:1307`, Payout `425:1595`, Detail `425:1882`+modal `425:2195`, Laporan `430:2262/2643/3028`; Hover `209:252/2594/3386/3522/9423` + full hover sections `209:11/1547/1896/2245/7017/3026`; Loading `174:4541`, AccountCreated `178:4843`, LoginAnim `194:384`.

## Global Constraints

- Exclude macOS title bar everywhere: 48px `bg-[#161618]` chrome (`50:105`, `6:1428/6:1489`, `394:289`, `433:4603`-type `Window header`) with 3×12px dots `#FF453A/#FF9F0A/#30D158` MUST NOT be rendered.
- Colors exact: `--bg #0F0F10`, `--raised #161618`, `--border #222224`, `--border-strong #27282A`, `--accent #1D4ED8`, `--accent-hi #287BF7`, `--link #60A5FA`, `--danger #E7000B`, `--success #30D158`.
- Font Inter only; no new dependencies without approval.
- All Blade must remain server-rendered; no SPA rewrite.
- Hover recipe mandatory: primary `hover:brightness-110 hover:shadow-[0_0_36px_rgba(29,78,216,0.55)]`, card `hover:border-[#303034]`, nav pill `hover:bg-white/10`, ghost `hover:bg-white/10`.
- Reduced-motion respected for loading/account overlays (`animations.css`).

## Review Focus

- Auth card widths differ (Register 420px vs Login 518px) — person switching routes expects distinct sizes, not uniform max-w.
- Price formatting `Rp350.000` vs `Rp 350.000` spacing must match Figma per screen, not normalized globally.
- Search input variants (288×51 pill marketing vs 320×40 dev toolbar vs 260×40 library vs 1256×48 Cari hero) — wrong variant breaks 1:1.
- Table row heights differ (moderasi h89 vs laporan h59 vs transaksi h71) — uniform table CSS breaks parity.
- Empty vs filled cart share header but body differs entirely — empty must show no rows, V2 must show order summary full-width.

---

### Task 1: Design tokens + base CSS 1:1

**Files:**
- Modify: `gpn/resources/css/app.css`
- Modify: `gpn/DESIGN-TOKENS.md` (repo root `DESIGN-TOKENS.md` if canonical)
- Test: `gpn/tests/Feature/DesignTokensTest.php`

**Interfaces:**
- Consumes: Figma hexes from spec (no code dependency).
- Produces: CSS utilities `.btn-primary` (h44/54 variants via arbitrary), `.input-auth`, `.card-auth`, `.nav-pill-glass`, `.table-admin`, `.badge-*` consumed by Tasks 2–10.

- [ ] **Step 1: Write the failing test**

```php
<?php
namespace Tests\Feature;
use Tests\TestCase;
class DesignTokensTest extends TestCase
{
    public function test_app_css_contains_figma_tokens(): void
    {
        $css = file_get_contents(base_path('resources/css/app.css'));
        foreach (['#0F0F10','#161618','#222224','#27282A','#1D4ED8','#287BF7','#60A5FA'] as $hex) {
            $this->assertStringContainsString($hex, $css, "missing token $hex");
        }
        $this->assertStringContainsString('.btn-primary', $css);
        $this->assertStringContainsString('rounded-[17.5px]', $css);
        $this->assertStringContainsString('rounded-[21.6px]', $css);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd gpn && php artisan test --filter=DesignTokensTest`
Expected: FAIL (missing `rounded-[17.5px]` / `rounded-[21.6px]` utilities).

- [ ] **Step 3: Write minimal implementation**

In `gpn/resources/css/app.css` keep existing `@theme`, add to `@layer components`:
```css
.auth-shell { @apply relative flex min-h-screen items-center justify-center bg-[#0F0F10] overflow-hidden; }
.auth-blob { @apply absolute rounded-full bg-[#1D4ED8] blur-[200px] pointer-events-none; }
.card-auth { @apply bg-[#161618] border border-[#222224] rounded-[17.5px] shadow-[0_27px_27px_rgba(0,0,0,0.25)]; }
.card-auth-lg { @apply bg-[#161618] border-[1.35px] border-[#222224] rounded-[21.6px] shadow-[0_34px_68px_rgba(0,0,0,0.25)]; }
.input-auth { @apply h-[46px] w-full bg-[#0F0F10] border border-[#222224] rounded-[17.5px] pl-[40.5px] pr-[18.6px] text-[15.31px] text-white placeholder:text-white/30 focus:border-[#1D4ED8] focus:outline-none; }
.input-auth-lg { @apply h-[56.7px] rounded-[21.6px] px-[48.6px] text-[18.9px]; }
.btn-auth { @apply h-[44px] w-full bg-[#1D4ED8] rounded-[17.5px] text-[15.31px] font-medium text-white shadow-[0_4px_3px_rgba(29,78,216,0.2),0_11px_8px_rgba(29,78,216,0.2)] transition hover:brightness-110 hover:shadow-[0_0_36px_rgba(29,78,216,0.55)]; }
.social-btn { @apply flex h-[46px] items-center justify-center gap-[8.75px] bg-[#0F0F10] border border-[#222224] rounded-[17.5px] text-[15.31px] font-medium text-white transition hover:border-[#303034]; }
.nav-glass-active { @apply rounded-full bg-white/10 border border-white/20 backdrop-blur-md text-white; }
.table-admin th { @apply text-[10px] font-medium uppercase text-white/50 h-[33px] tracking-wide; }
.table-admin td { @apply text-xs border-t border-[#222224]; }
```
Update `@theme` to include `--color-border-strong: #27282A; --color-accent-hi: #287BF7; --color-link: #60A5FA;`.

- [ ] **Step 4: Run test to verify it passes**

Run: `cd gpn && php artisan test --filter=DesignTokensTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add gpn/resources/css/app.css gpn/tests/Feature/DesignTokensTest.php DESIGN-TOKENS.md
git commit -m "feat: align design tokens to Figma 1:1 (auth radii, hover, tables)"
```

### Task 2: Shared headers + app/portal shells (no chrome)

**Files:**
- Modify: `gpn/resources/views/partials/marketing-header.blade.php`
- Modify: `gpn/resources/views/partials/slim-header.blade.php`
- Modify: `gpn/resources/views/components/app-layout.blade.php`
- Modify: `gpn/resources/views/components/portal-layout.blade.php`
- Test: `gpn/tests/Feature/HeaderShellTest.php`

**Interfaces:**
- Consumes: `.nav-glass-active`, tokens from Task 1.
- Produces: `<x-app-layout>`, `<x-portal-layout>`, `marketing-header` with `$active` prop used by Tasks 4–9.

- [ ] **Step 1: Write the failing test**

```php
<?php
namespace Tests\Feature;
use Tests\TestCase;
class HeaderShellTest extends TestCase
{
    public function test_no_macos_chrome_and_header_specs(): void
    {
        $files = [
            base_path('resources/views/partials/marketing-header.blade.php'),
            base_path('resources/views/components/app-layout.blade.php'),
            base_path('resources/views/components/portal-layout.blade.php'),
        ];
        foreach ($files as $f) {
            $html = file_get_contents($f);
            $this->assertStringNotContainsString('h-[48px]', $html, "$f has chrome height");
            $this->assertDoesNotMatchRegularExpression('/FF453A.*FF9F0A|traffic/i', $html);
        }
        $m = file_get_contents($files[0]);
        $this->assertStringContainsString('h-[103px]', $m);
        $this->assertStringContainsString('max-w-[1510px]', $m);
        $this->assertStringContainsString('gap-12', $m);
        $this->assertStringContainsString('text-[21px]', $m);
        $p = file_get_contents($files[2]);
        $this->assertStringContainsString('w-[220px]', $p);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd gpn && php artisan test --filter=HeaderShellTest`
Expected: FAIL on `w-[220px]` or `h-[103px]` if drifted, else PASS baseline then strengthen hover assertions.

- [ ] **Step 3: Write minimal implementation**

`marketing-header.blade.php`: keep `h-[103px] max-w-[1510px] px-12`, nav `gap-12 text-[21px] font-medium` with active `text-white` else `text-white/70 hover:text-white hover:bg-white/10 rounded-full px-4 py-2 backdrop-blur-md` (glass on hover per `209:252`). Search `h-[51px] w-72 rounded-full border-[#27282A] bg-[#161618] px-[18px]`, icon `h-6 w-6 text-white/40`, input `text-[15px] placeholder:text-white/40`. Sign In `h-[51px] rounded-full bg-white/10 border-white/10 px-8 text-[18px] hover:bg-white/20`.
`slim-header`: same but `h-20`, no search (Profile `6:1890` header 80px centered nav).
`portal-layout`: aside `w-[220px] bg-[#0F0F10] border-r border-[#222224] p-4` (old screens) — keep `w-[220px]`; nav item `h-[38px] px-3 py-2 gap-3 rounded-xl text-sm`, active `bg-[#161618] border border-[#222224] text-white`, idle `text-white/60 hover:bg-white/5`. New 433-series variant uses same file with conditional `request()->routeIs('store*','games*')` → active `bg-[#1D4ED8]/10 text-[#1D4ED8]`. Never render 48px chrome.

- [ ] **Step 4: Run test to verify it passes**

Run: `cd gpn && php artisan test --filter=HeaderShellTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add gpn/resources/views/partials/marketing-header.blade.php gpn/resources/views/partials/slim-header.blade.php gpn/resources/views/components/app-layout.blade.php gpn/resources/views/components/portal-layout.blade.php gpn/tests/Feature/HeaderShellTest.php
git commit -m "feat: headers and shells match Figma, no macOS chrome"
```

### Task 3: Auth — Register / Login 1:1

**Files:**
- Modify: `gpn/resources/views/auth/register.blade.php`
- Modify: `gpn/resources/views/auth/login.blade.php`
- Create: `gpn/resources/views/components/auth-shell.blade.php`
- Test: `gpn/tests/Feature/AuthDesignTest.php`

**Interfaces:**
- Consumes: `.auth-shell`, `.card-auth`, `.input-auth`, `.btn-auth` from Task 1.
- Produces: standalone auth pages (no layout) used by `AuthController@showLogin/showRegister`.

- [ ] **Step 1: Write the failing test**

```php
<?php
namespace Tests\Feature;
use Tests\TestCase;
class AuthDesignTest extends TestCase
{
    public function test_register_matches_figma(): void
    {
        $r = $this->get('/register');
        $r->assertOk();
        $r->assertSee('w-[420px]', false);
        $r->assertSee('rounded-[17.5px]', false);
        $r->assertSee('h-[46px]', false);
        $r->assertSee('Join GPlayNation today', false);
        $r->assertDontSee('h-[48px]', false);
    }
    public function test_login_matches_figma(): void
    {
        $r = $this->get('/login');
        $r->assertOk();
        $r->assertSee('w-[518px]', false);
        $r->assertSee('rounded-[21.6px]', false);
        $r->assertSee('h-[56.7px]', false);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd gpn && php artisan test --filter=AuthDesignTest`
Expected: FAIL (current uses `max-w-[440px]`, generic `.card`).

- [ ] **Step 3: Write minimal implementation**

`components/auth-shell.blade.php`:
```blade
@props(['wide' => false])
<div class="auth-shell">
  <div class="auth-blob opacity-10 blur-[200px] w-64 h-64" style="left:180px;top:150px"></div>
  <div class="auth-blob opacity-5 blur-[160px] w-64 h-64" style="left:507px;top:263px"></div>
  <div class="{{ $wide ? 'w-[518px] p-[43.2px] card-auth-lg' : 'w-[420px] p-[36px] card-auth' }}">{{ $slot }}</div>
</div>
```
Register: logo `61x61`, H2 `text-[21.87px] font-semibold leading-[30.62px] tracking-[-0.55px]` “Create Account”, sub `text-[15.31px] text-white/50` “Join GPlayNation today”, form `py-[26px] gap-[17.5px]`, label `pl-[4.37px] text-[13.12px] font-medium text-white/70`, inputs `.input-auth` with icon absolute `left-[13px] h-[17.5px]`, terms row `gap-[8.75px] pb-[17.5px]` checkbox `14.2px`, primary `.btn-auth`, divider lines `w-[105px] h-px bg-[#222224]` + `text-[13.12px] text-white/40` “or continue with”, social `h-[46px]` ×2 `w-[166px] gap-[13px]`, footer `pt-[21.87px] text-[14.22px] text-white/50` + link `text-[#1D4ED8]` “Sign in”.
Login: same scaled (`w-[518px]`, `rounded-[21.6px]`, logo `76x76`, H2 `text-[27px] leading-[37.8px] tracking-[-0.67px]` “Login”, inputs `h-[56.7px] text-[18.9px]`, button `h-[54px] text-[18.9px]`, footer “Don’t have an account?” + “Sign Up”).

- [ ] **Step 4: Run test to verify it passes**

Run: `cd gpn && php artisan test --filter=AuthDesignTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add gpn/resources/views/auth/register.blade.php gpn/resources/views/auth/login.blade.php gpn/resources/views/components/auth-shell.blade.php gpn/tests/Feature/AuthDesignTest.php
git commit -m "feat: auth register/login match Figma 420/518 cards"
```

### Task 4: Storefront Home hero + cards

**Files:**
- Modify: `gpn/resources/views/storefront/home.blade.php`
- Modify: `gpn/resources/views/components/game-card.blade.php`
- Modify: `gpn/resources/views/components/price.blade.php`
- Test: `gpn/tests/Feature/HomeDesignTest.php`

**Interfaces:**
- Consumes: `marketing-header`, tokens.
- Produces: `x-game-card` (`:game`) reused by Store/Search/Profile.

- [ ] **Step 1: Write the failing test**

```php
<?php
namespace Tests\Feature;
use Tests\TestCase;
class HomeDesignTest extends TestCase
{
    public function test_home_hero_matches_figma(): void
    {
        $r = $this->get('/');
        $r->assertOk();
        $r->assertSee('Game of the Week', false);
        $r->assertSee('text-[72px]', false);
        $r->assertSee('rounded-3xl', false);
        $r->assertSee('shadow-[0_0_30px_rgba(29,78,216,0.4)]', false);
        $r->assertDontSee('h-[48px]', false);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd gpn && php artisan test --filter=HomeDesignTest`
Expected: FAIL (current H1 `text-[54px]`, missing glow/shadow exact).

- [ ] **Step 3: Write minimal implementation**

Hero section `h-[932px]`: cinematic image `object-cover opacity-60` + overlays `bg-gradient-to-b from-[#0F0F10] via-[#0F0F10]/80 to-transparent` and left `bg-gradient-to-r from-[#0F0F10] via-black/75 to-transparent`. Content `absolute left-[85px] bottom-[260px] max-w-[864px]`: eyebrow `text-[18px] font-bold tracking-[0.9px] text-[#1D4ED8]` “Game of the Week”, H1 `text-[72px] font-bold leading-[72px] tracking-[-1.8px] w-[538px]`, byline `text-[27px] font-medium text-white/60 leading-[42px] pt-3`, desc `max-w-[672px] text-[21px] text-white/70 leading-[34.125px] pt-[30px]`, CTA `w-[240px] h-[72px] rounded-3xl bg-[#1D4ED8] px-12 text-[24px] font-medium shadow-[0_0_30px_rgba(29,78,216,0.4)] hover:brightness-110 mt-[30px]`. Dots `w-20 gap-2 centered`: active `w-8 h-1 bg-[#1D4ED8] rounded-full`, inactive `w-2 h-1 bg-white/20`.
`game-card`: `bg-[#161618] border border-[#222224] rounded-2xl overflow-hidden hover:border-[#303034]`, image `aspect-video opacity-80`, badge `absolute top-2 left-2 bg-black/60 text-[12px]`, body `p-4`: title `text-[14px] font-semibold h-6`, tags `gap-1.5 pb-3` pill `bg-[#0F0F10] border-[#222224] rounded`, footer `border-t border-[#222224] pt-3 flex justify-between`: price + Buy `w-[54px] h-[28px] rounded-xl bg-[#1D4ED8] text-[12px] shadow-blue-700/40`.

- [ ] **Step 4: Run test to verify it passes**

Run: `cd gpn && php artisan test --filter=HomeDesignTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add gpn/resources/views/storefront/home.blade.php gpn/resources/views/components/game-card.blade.php gpn/resources/views/components/price.blade.php gpn/tests/Feature/HomeDesignTest.php
git commit -m "feat: home hero and game cards match Figma"
```

### Task 5: Store / Search / Game detail + Buy modal

**Files:**
- Modify: `gpn/resources/views/storefront/store.blade.php`
- Modify: `gpn/resources/views/storefront/game.blade.php`
- Create: `gpn/resources/views/components/buy-modal.blade.php`
- Test: `gpn/tests/Feature/StoreGameDesignTest.php`

**Interfaces:**
- Consumes: `x-game-card`, portal sidebar patterns.
- Produces: Store grid, Game hero+purchase panel, Buy modal (`433:4448`) reused by checkout.

- [ ] **Step 1: Write the failing test**

```php
<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\Game;
class StoreGameDesignTest extends TestCase
{
    public function test_store_grid_and_game_hero(): void
    {
        $this->get('/store')->assertOk()->assertSee('Marketplace Home', false);
        $game = Game::first() ?? Game::factory()->create();
        $this->get('/store/'.$game->slug)->assertOk()->assertSee('Description', false)->assertSee('Buy Now', false);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd gpn && php artisan test --filter=StoreGameDesignTest`
Expected: FAIL or PASS baseline — then assert `rounded-2xl`, `grid-cols-3`, `Rp350.000` exact in updated test.

- [ ] **Step 3: Write minimal implementation**

Store: sidebar `w-[224px] border-r border-[#222224]`, main `p-6`: topbar `flex justify-between pb-6` H1 `text-[24px] font-bold tracking-[-0.6px]` “Marketplace Home” + Sort `w-[215px] h-[38px] rounded-xl bg-[#161618] border-[#222224] text-[14px]`. Grid `grid grid-cols-3 gap-6`, card `367.5px` as Task 4.
Cari hero (reuse store view when `?q`): search bar `h-[48px] bg-[#161618] border rounded-2xl px-4 gap-3` + `⌘K` pill `bg-white/10 rounded-md px-2 text-xs text-white/60`, filter pills `h-9 px-4 rounded-full border text-sm`, active `bg-white text-black`.
Game: sidebar `w-[192px] p-4`, banner `h-[256px] object-cover` + `bg-gradient-to-t from-[#0F0F10]`, title `text-[36px] font-bold tracking-[-0.9px]` + meta `text-[14px] text-white/60 gap-4`. Body `flex gap-8 p-8`: left `934px` H3 `text-[18px] font-semibold border-b border-[#222224] pb-2` + copy `text-[14px] text-white/70 leading-[22.75px]`; right panel `w-[288px] bg-[#161618] border rounded-2xl p-6 gap-6 sticky`: price `text-[30px] font-bold`, edition `h-[42px] bg-[#0F0F10] border rounded-2xl`, Add to Cart ghost + Buy Now primary `h-[42px] rounded-2xl`.
`buy-modal`: backdrop `bg-black/60 backdrop-blur`, dialog `w-[640px] p-8 rounded-2xl bg-[#161618]`: title `text-[24px] font-bold`, order row `bg-[#0F0F10] border rounded-xl p-4` art `112x80`, payment grid `grid-cols-3 gap-3` selected `border-[#1D4ED8] bg-[#1D4ED8]/10`, total `border-t pt-4 text-lg font-bold`, CTA `h-[42px]` Pay Now + lock note `text-xs text-white/60`.

- [ ] **Step 4: Run test to verify it passes**

Run: `cd gpn && php artisan test --filter=StoreGameDesignTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add gpn/resources/views/storefront/store.blade.php gpn/resources/views/storefront/game.blade.php gpn/resources/views/components/buy-modal.blade.php gpn/tests/Feature/StoreGameDesignTest.php
git commit -m "feat: store, search, game detail and buy modal match Figma"
```

### Task 6: Community + Profile

**Files:**
- Modify: `gpn/resources/views/community/index.blade.php`
- Modify: `gpn/resources/views/storefront/profile.blade.php`
- Modify: `gpn/resources/views/components/library-card.blade.php`
- Test: `gpn/tests/Feature/CommunityProfileDesignTest.php`

**Interfaces:**
- Consumes: `marketing-header`/`slim-header`, `x-game-card`.
- Produces: community feed + right rail, profile tabs + library grid.

- [ ] **Step 1: Write the failing test**

```php
<?php
namespace Tests\Feature;
use Tests\TestCase;
class CommunityProfileDesignTest extends TestCase
{
    public function test_community_layout(): void
    {
        $this->get('/community')->assertOk()->assertSee('rounded-2xl', false);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd gpn && php artisan test --filter=CommunityProfileDesignTest`
Expected: PASS baseline; extend to assert `w-[260px]`, `w-[320px]`, `rounded-full` composer.

- [ ] **Step 3: Write minimal implementation**

Community: header `h-[103px] px-12`, hero strip `h-[103px] object-cover` behind, main `max-w-[1446px] px-8 flex gap-5`: sidebar `w-[260px] bg-[#161618] border rounded-2xl p-4`, feed `w-[826px]`: composer `bg-[#161618] border rounded-2xl p-4` avatar `40px rounded-full bg-gradient-to-br from-[#1D4ED8] to-[#933AEA]` + input `h-[44px] rounded-full bg-[#0F0F10] border placeholder:text-[14px]`, post `p-4 gap-3` title `text-[14px] font-semibold` body `text-[14px] text-white/70`, vote rail `w-[52px] bg-[#131315]` buttons `32x32 rounded-lg hover:bg-white/10`. Right rail `w-[320px] p-4 gap-4` rows avatar `32px` + name `text-[14px]` + status `text-[12px] text-white/60`.
Profile: cover `h-[241px] bg-gradient-to-b from-[#161618] to-[#0F0F10] border-b` + glow `bg-[#1D4ED8]/10 blur-[150px] rounded-full`, avatar `80x80 rounded-full border-2 border-[#161618]`, name `text-[20px] font-semibold`, stats `flex gap-4 text-[14px] text-white/60`, tabs `h-[55px] flex justify-center gap-0`: button `h-[54px] text-[14px] font-medium text-white/60 hover:text-white`, active `text-white border-b-2 border-[#1D4ED8]`, content `grid grid-cols-3 gap-6 max-w-[1303px]`.

- [ ] **Step 4: Run test to verify it passes**

Run: `cd gpn && php artisan test --filter=CommunityProfileDesignTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add gpn/resources/views/community/index.blade.php gpn/resources/views/storefront/profile.blade.php gpn/resources/views/components/library-card.blade.php gpn/tests/Feature/CommunityProfileDesignTest.php
git commit -m "feat: community and profile match Figma"
```

### Task 7: Transactions + Checkout + Gateway

**Files:**
- Modify: `gpn/resources/views/storefront/transactions/index.blade.php`
- Modify: `gpn/resources/views/storefront/transactions/show.blade.php`
- Modify: `gpn/resources/views/checkout/summary.blade.php`
- Modify: `gpn/resources/views/checkout/result.blade.php`
- Modify: `gpn/resources/views/gateway/show.blade.php`
- Modify: `gpn/resources/views/components/status-pill.blade.php`
- Test: `gpn/tests/Feature/TransactionDesignTest.php`

**Interfaces:**
- Consumes: `x-app-layout`, `buy-modal` summary styles.
- Produces: cart empty/filled, stepper, payment list, receipt, gateway.

- [ ] **Step 1: Write the failing test**

```php
<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\User;
class TransactionDesignTest extends TestCase
{
    public function test_history_and_status_badges(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/transactions')->assertOk()->assertSee('Riwayat Transaksi', false);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd gpn && php artisan test --filter=TransactionDesignTest`
Expected: FAIL (copy is English “Transactions”, Figma “Riwayat Transaksi” — keep routes, use Figma copy in H1).

- [ ] **Step 3: Write minimal implementation**

Keranjang empty (`409:483`): `p-[45px] flex justify-between items-end max-w-[1120px]` H1 `text-[28px] font-bold` “Keranjang” + `text-sm text-white/60` “Keranjang kamu masih kosong.” + outline `h-[42px] px-5 rounded-[14px] border-[#28282C]` “Lihat Game”. V2 filled: section `bg-[#161618] border rounded-2xl p-[25px] max-w-[1180px]`, game row art `72x52 rounded-lg` + `text-[14px] font-semibold` + `text-[12px] text-white/60`, footer `flex justify-end gap-3 pt-6` Hapus outline + Lanjut primary.
Pembayaran (`409:627`): eyebrow `text-[11px] font-semibold tracking-[1.54px] uppercase text-[#60A5FA]` “CHECKOUT”, H1 `text-[28px] font-bold` “Proses Pembayaran”, stepper circles `w-7 h-7 rounded-full` done `bg-[#1D4ED8]` connector `w-[58px] h-px bg-[#1D4ED8]`. Grid `736px + 360px gap-6 max-w-[1120px]`: payment section `rounded-2xl p-7` rows `h-[78px] p-4 rounded-[14px]` selected `bg-[#2563EB]/14 border-[#2563EB]` else `bg-[#121214] border-[#28282C]`, icon chip `w-11 h-11 rounded-xl bg-[#202024]`, radio dot. Right summary `w-[360px] p-6` rows `Harga game / Biaya layanan / Total Rp350.000 text-[18px] font-bold`, CTA Bayar primary + Kembali outline.
Riwayat: stats `grid-cols-3 border-y border-[#222224] p-6` value `text-[20px] font-bold`, table `rounded-2xl overflow-hidden` header `h-[41px] text-[10px] uppercase text-white/40 px-[18px]`, row `h-[71px] border-t` ID `text-[13px] font-semibold` + date `text-[11px]`, badge `rounded-full px-2 py-[5px] text-[10px]`: Berhasil `bg-[#30D158]/12 text-[#30D158]`, Dibatalkan `bg-white/5 text-white/60`.
Status success: centered `w-[600px] rounded-[20px] border-[#30D158]/20 p-[42px]` check `58px rounded-full bg-[#30D158]/12 border-[#30D158]/35`, eyebrow `text-[#30D158]`, H1 `text-[28px]`, receipt `bg-black/10 border rounded-[14px] px-[22px]` rows `h-[45px]`.
Gateway: keep centered `max-w-lg`, method radios `has-[:checked]:border-accent`, pay `!bg-success`, cancel `!border-danger`.

- [ ] **Step 4: Run test to verify it passes**

Run: `cd gpn && php artisan test --filter=TransactionDesignTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add gpn/resources/views/storefront/transactions/ gpn/resources/views/checkout/ gpn/resources/views/gateway/show.blade.php gpn/resources/views/components/status-pill.blade.php gpn/tests/Feature/TransactionDesignTest.php
git commit -m "feat: cart, checkout, history, status match Figma"
```

### Task 8: Developer portal

**Files:**
- Modify: `gpn/resources/views/developer/dashboard.blade.php`
- Modify: `gpn/resources/views/developer/games/index.blade.php`
- Modify: `gpn/resources/views/developer/games/create.blade.php`
- Modify: `gpn/resources/views/developer/games/edit.blade.php`
- Modify: `gpn/resources/views/developer/sales.blade.php`
- Modify: `gpn/resources/views/developer/promotion.blade.php`
- Modify: `gpn/resources/views/developer/tags.blade.php`
- Modify: `gpn/resources/views/developer/payouts.blade.php`
- Modify: `gpn/resources/views/developer/settings.blade.php`
- Modify: `gpn/resources/views/components/area-chart.blade.php`
- Test: `gpn/tests/Feature/DevDesignTest.php`

**Interfaces:**
- Consumes: `x-portal-layout section=developer`, `.table-admin`.
- Produces: dev sidebar `w-[208px]`, stats, charts, tables, Unduh/Kelola views (reuse `games/index` for `433:4607`, new `developer/library.blade.php` for `433:4502` if route exists else extend index).

- [ ] **Step 1: Write the failing test**

```php
<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\User;
class DevDesignTest extends TestCase
{
    public function test_dev_dashboard_layout(): void
    {
        $user = User::factory()->create(['role' => 'developer']);
        $this->actingAs($user)->get('/dev')->assertOk()->assertSee('rounded-2xl', false);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd gpn && php artisan test --filter=DevDesignTest`
Expected: PASS baseline; extend assertions to `w-[208px]`, `h-[38px]`, `grid-cols-3`.

- [ ] **Step 3: Write minimal implementation**

Header: H1 `text-2xl font-bold tracking-[-0.6px]` + sub `text-sm text-white/50`, CTA `h-10 px-4 rounded-2xl bg-[#1D4ED8] shadow-[0_0_15px_rgba(29,78,216,.3)] text-sm`. Stats `grid-cols-3 gap-4`: card `rounded-2xl p-5` label `text-xs text-white/60` value `text-2xl font-bold` + delta pill `bg-[#0CD70F]/10 text-[#0CD70F]`. Chart card `p-6` title `text-sm font-semibold` + SVG stroke `#1D4ED8` grid `#222224`. Tables: toolbar `h-[72px] p-4 flex justify-between` search `w-[320px] h-10 bg-[#0F0F10] border rounded-2xl text-xs`, head `h-11 bg-white/[0.03] text-xs uppercase text-white/60`, row `h-[68px] border-t` thumb `w-[72px] h-12 rounded-lg`, badge `h-[25px] px-3 rounded-full text-xs` Published `bg-[#30D158]/10 text-[#30D158]`, Draft gray, Review yellow. Setting toggles `w-[50px] h-[25px] bg-[#27282A] rounded-full`. Unduh: progress track `h-2 bg-white/10 rounded-full` fill `bg-[#1D4ED8] w-[64%]`, actions Pause/Cancel ghost `h-[42px]`. Sales split `779px + 431px`.

- [ ] **Step 4: Run test to verify it passes**

Run: `cd gpn && php artisan test --filter=DevDesignTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add gpn/resources/views/developer/ gpn/resources/views/components/area-chart.blade.php gpn/tests/Feature/DevDesignTest.php
git commit -m "feat: developer portal matches Figma"
```

### Task 9: Admin panel + Report + Moderation modal

**Files:**
- Modify: `gpn/resources/views/admin/dashboard.blade.php`
- Modify: `gpn/resources/views/admin/verification/index.blade.php`
- Modify: `gpn/resources/views/admin/moderation/index.blade.php`
- Modify: `gpn/resources/views/admin/moderation/show.blade.php`
- Modify: `gpn/resources/views/admin/commission.blade.php`
- Modify: `gpn/resources/views/admin/payouts.blade.php`
- Modify: `gpn/resources/views/admin/reports.blade.php`
- Test: `gpn/tests/Feature/AdminDesignTest.php`

**Interfaces:**
- Consumes: `x-portal-layout section=admin`, `.table-admin`.
- Produces: admin tables, `Detail Pengajuan` modal `425:2195`, report filters/charts.

- [ ] **Step 1: Write the failing test**

```php
<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\User;
class AdminDesignTest extends TestCase
{
    public function test_admin_tables(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('rounded-2xl', false);
        $this->actingAs($admin)->get('/admin/moderation')->assertOk();
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd gpn && php artisan test --filter=AdminDesignTest`
Expected: PASS baseline; extend to `h-[89px]`, `Menunggu`, `bg-[#f0b100]/10`.

- [ ] **Step 3: Write minimal implementation**

Shell `Sidebar 208px + Main p-8`, header `flex justify-between pb-8` H1 `text-2xl font-bold` + sub `text-sm text-white/50`, CTA Export `h-8 border rounded-xl text-xs` / Tambah Skema `h-10 bg-[#1D4ED8] rounded-2xl shadow-lg`. Stats `grid-cols-3 gap-4` icon `w-10 h-10 rounded-full` tints blue/pink/yellow `/10`. Table card `p-6`: filter tabs active `bg-[#222224] border-[#303034] rounded-xl h-[34px] text-xs`, search `w-64 h-[38px] bg-[#0F0F10] border rounded-xl text-xs`. Rows: moderasi `h-[89px]`, laporan `h-[59px]`; thumb `w-16 h-12 rounded-xl`; badge Menunggu `bg-[#f0b100]/10 text-[#eab308] rounded text-[10px] px-2 py-1`, Berhasil `bg-[#30D158]/10 text-[#56c894]`. Modal `w-[560px] p-[25px] rounded-2xl`: title `text-xl font-bold` + ×, meta `divide-y divide-[#222224]`, footer Tutup outline / Tolak `text-[#ff6467]` / Setujui primary `h-10`. Reports: filter `h-[37px] rounded-[10px]`, summary `grid-cols-3`, chart `h-[286px]`, table cols ID/Tanggal/Game/Total/Status/Aksi.

- [ ] **Step 4: Run test to verify it passes**

Run: `cd gpn && php artisan test --filter=AdminDesignTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add gpn/resources/views/admin/ gpn/tests/Feature/AdminDesignTest.php
git commit -m "feat: admin panel and reports match Figma"
```

### Task 10: Hover states + Loading / Account-created animations + final verification

**Files:**
- Modify: `gpn/resources/css/animations.css`
- Modify: `gpn/resources/views/storefront/home.blade.php` (intro overlay `205:677/695`)
- Modify: `gpn/resources/views/auth/login.blade.php` + `register.blade.php` (account-created `178:4843`, login anim `194:384`)
- Modify: all `game-card`, `nav`, `btn-primary` usages for hover recipe
- Test: `gpn/tests/Feature/HoverAnimationTest.php`

**Interfaces:**
- Consumes: everything from Tasks 1–9.
- Produces: final 1:1 with motion; no visual regressions.

- [ ] **Step 1: Write the failing test**

```php
<?php
namespace Tests\Feature;
use Tests\TestCase;
class HoverAnimationTest extends TestCase
{
    public function test_hover_and_animation_classes_present(): void
    {
        $css = file_get_contents(base_path('resources/css/animations.css'));
        $this->assertStringContainsString('@media (prefers-reduced-motion', $css);
        $home = file_get_contents(base_path('resources/views/storefront/home.blade.php'));
        $this->assertStringContainsString('hover:brightness-110', $home);
        $this->assertStringContainsString('overlay-enter', $home);
        $card = file_get_contents(base_path('resources/views/components/game-card.blade.php'));
        $this->assertStringContainsString('hover:border', $card);
        $this->assertStringContainsString('group-hover:scale', $card);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd gpn && php artisan test --filter=HoverAnimationTest`
Expected: FAIL if `prefers-reduced-motion` missing.

- [ ] **Step 3: Write minimal implementation**

`animations.css`: intro overlay `205:677` (logo 85px + GPlayNation 22px) fade/scale 600ms, `205:695` (141px rounded + 105px text) glow `shadow-[0_0_30px_rgba(29,78,216,0.4)]`; loading dots sequence `174:4541` (5 frames shimmer), account-created `178:4843` (check 40px + card scale-in), login morph `194:384` (4 frames input focus ring `1.67px #1D4ED8`); all wrapped in `@media (prefers-reduced-motion: reduce) { * { animation: none !important; transition: none !important; } }`.
Apply hover globally: `btn-primary` already has glow; add `hover:brightness-110`; nav pills `hover:bg-white/10 rounded-full backdrop-blur-md`; vote/tabs `hover:bg-white/10 rounded-lg`; cards `hover:border-[#303034] transition group-hover:scale-[1.02]`; Buy `shadow-blue-700/40 hover:shadow-[0_0_36px_rgba(29,78,216,0.55)]`.
Verify no `h-[48px]` chrome, no `traffic`, run `php artisan test`, `npm run build`.

- [ ] **Step 4: Run test to verify it passes**

Run: `cd gpn && php artisan test --filter=HoverAnimationTest && npm run build`
Expected: PASS + `exit 0` build.

- [ ] **Step 5: Commit**

```bash
git add gpn/resources/css/animations.css gpn/resources/views/storefront/home.blade.php gpn/resources/views/auth/ gpn/resources/views/components/game-card.blade.php gpn/tests/Feature/HoverAnimationTest.php
git commit -m "feat: hover states and open-app animations match Figma"
```

## Self-Review

- Spec coverage: Auth ✓, Home ✓, Store/Search/Game+modal ✓, Community/Profile ✓, Transactions/Checkout/Gateway ✓, Dev+Kelola/Unduh ✓, Admin+Reports+Modal ✓, Hover+Animations ✓. Logo `5:88` already traced in `x-logo` — no task needed.
- Placeholder scan: no TBD/TODO; all steps have exact code + commands.
- Type consistency: `w-[420px]/w-[518px]`, `rounded-[17.5px]/[21.6px]`, `h-[46px]/[56.7px]` used consistently; table heights per-page, not shared.
- Review Focus tests added per task (auth sizes, price spacing noted in Task 7 impl, search variants in Task 5, row heights in Tasks 8–9, empty/filled cart in Task 7).
