<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/store', [StoreController::class, 'index'])->name('store.index');
Route::get('/store/{game:slug}', [StoreController::class, 'show'])->name('store.show');

Route::get('/community', fn () => 'ok')->name('community.index'); // placeholder — Task 11 replaces

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/showcase/{library}', [ProfileController::class, 'showcase'])->name('profile.showcase');
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/{transaksi:order_id}', [TransactionController::class, 'show'])->name('transactions.show');
});

Route::get('/dev', fn () => 'ok')->middleware(['auth', 'role:developer']); // placeholder — Task 8 replaces
Route::get('/admin', fn () => 'ok')->middleware(['auth', 'role:admin']);    // placeholder — Task 10 replaces

Route::middleware('auth')->group(function () {
    Route::post('/wishlist/{game}', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
    Route::get('/games/{game:slug}/download', [DownloadController::class, 'show'])->name('games.download');
});
