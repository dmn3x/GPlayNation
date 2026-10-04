<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\DeveloperController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OAuthController;
use App\Http\Controllers\StoreController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

/*
| GPlayNation routes — mapped from the SRS use-case diagrams.
*/

// ---------- Publik ----------
Route::get('/', [HomeController::class, 'index'])->name('home');

// SRS 3.1.2.1 / 3.1.2.2 — Katalog & detail game
Route::get('/store', [StoreController::class, 'index'])->name('store.index');
Route::get('/store/{game}', [StoreController::class, 'show'])->name('store.show');

// SRS 4.3 Community
Route::get('/community', [CommunityController::class, 'index'])->name('community.index');

// ---------- Autentikasi (F-01, F-02) ----------
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
    Route::get('/oauth/{provider}', [OAuthController::class, 'redirect'])->name('oauth.redirect');
    Route::get('/oauth/{provider}/callback', [OAuthController::class, 'callback'])->name('oauth.callback');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// ---------- Pengguna terautentikasi ----------
Route::middleware('auth')->group(function () {
    // SRS 3.1.1.3 Kelola Profil
    Route::get('/profile', [AuthController::class, 'showProfile'])->name('profile.show');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');

    // SRS 3.1.2.3 Mengunduh Game
    Route::get('/store/{game}/download', [StoreController::class, 'download'])->name('store.download');
    Route::post('/store/{game}/wishlist', [StoreController::class, 'toggleWishlist'])->name('store.wishlist');

    // SRS 3.1.3 Checkout (F-05, F-06)
    Route::get('/checkout/{type}/{id}', [CheckoutController::class, 'review'])
        ->whereIn('type', ['game', 'iap'])->name('checkout.review');
    Route::post('/checkout/{type}/{id}', [CheckoutController::class, 'store'])
        ->whereIn('type', ['game', 'iap'])->name('checkout.store');
    // SRS 3.1.3.4 Riwayat Transaksi
    Route::get('/transactions', [CheckoutController::class, 'history'])->name('transactions.index');

    // SRS 4.3 Community
    Route::post('/community', [CommunityController::class, 'store'])->name('community.store');
    Route::post('/community/{post}/vote', [CommunityController::class, 'vote'])->name('community.vote');
});

// ---------- Developer (SRS 3.1.2 / 3.1.5 sisi developer) ----------
Route::middleware(['auth', 'role:developer'])->prefix('dev')->group(function () {
    Route::get('/', [DeveloperController::class, 'dashboard'])->name('developer.dashboard');
    Route::get('/sales', [DeveloperController::class, 'sales'])->name('developer.sales');
    Route::post('/games', [DeveloperController::class, 'store'])->name('developer.games.store');
    Route::put('/games/{game}', [DeveloperController::class, 'update'])->name('developer.games.update');
    Route::post('/games/{game}/iaps', [DeveloperController::class, 'storeIap'])->name('developer.iap.store');
    Route::get('/tags', [DeveloperController::class, 'tags'])->name('developer.tags');
    Route::get('/promotion', [DeveloperController::class, 'promotion'])->name('developer.promotion');
    Route::post('/promotion', [DeveloperController::class, 'promotion'])->name('developer.promotion.store');
    Route::get('/settings', [DeveloperController::class, 'settings'])->name('developer.settings');
    Route::post('/settings', [DeveloperController::class, 'settings'])->name('developer.settings.store');
    Route::get('/payouts', [DeveloperController::class, 'payouts'])->name('developer.payouts');
});

// ---------- Admin (SRS 3.1.4, 3.1.5.3–3.1.5.5) ----------
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('admin.dashboard');

    // SRS 3.1.1.4 Verifikasi Akun
    Route::get('/users', [AdminController::class, 'users'])->name('admin.users');
    Route::post('/users/{user}/verify', [AdminController::class, 'verifyUser'])->name('admin.users.verify');

    // SRS 3.1.4.1 Moderasi Game
    Route::get('/moderation', [AdminController::class, 'moderation'])->name('admin.moderation');
    Route::post('/games/{game}/moderate', [AdminController::class, 'moderateGame'])->name('admin.moderation.store');

    // SRS 3.1.4.2 Kelola Komisi
    Route::get('/komisi', [AdminController::class, 'komisi'])->name('admin.komisi');
    Route::post('/komisi', [AdminController::class, 'komisi'])->name('admin.komisi.store');

    // SRS 3.1.4.3 Payout
    Route::get('/payouts', [AdminController::class, 'payouts'])->name('admin.payouts');
    Route::post('/payouts/{user}', [AdminController::class, 'processPayout'])->name('admin.payouts.store');

    // SRS 3.1.5.3–3.1.5.5 Pelaporan
    Route::get('/reports', [AdminController::class, 'reports'])->name('admin.reports');
});

// SRS 3.1.3.3 — Midtrans webhook callback. Server-to-server, so it carries no
// CSRF token; authenticity is established by the signature check in the action.
Route::post('/webhook/midtrans', [AdminController::class, 'paymentWebhook'])
    ->withoutMiddleware(ValidateCsrfToken::class)
    ->name('webhook.midtrans');