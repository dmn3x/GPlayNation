<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GameStatus;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\KomisiTransaksi;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $month = now();

        $commissionThisMonth = (string) KomisiTransaksi::whereHas(
            'transaksi.pembayaran',
            fn ($q) => $q->whereBetween('paid_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
        )->sum('amount');

        return view('admin.dashboard', [
            'pendingDevelopers' => User::where('role', 'developer')->where('status', UserStatus::Pending)->count(),
            'pendingGames' => Game::where('status', GameStatus::PendingReview)->count(),
            'totalUsers' => User::count(),
            'todayTransactions' => Transaksi::whereDate('created_at', today())->count(),
            'commissionThisMonth' => $commissionThisMonth,
        ]);
    }
}
