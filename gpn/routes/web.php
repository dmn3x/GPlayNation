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
