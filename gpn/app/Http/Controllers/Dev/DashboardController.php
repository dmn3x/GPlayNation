<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\Download;
use App\Models\KomisiTransaksi;
use App\Models\Pembayaran;
use App\Models\Transaksi;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $developer = auth()->user()->developer;
        $gameIds = $developer->games()->pluck('id');

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
            ->map(fn ($day) => ['label' => (string) $day, 'value' => (float) ($daily[now()->format('Y-m-').str_pad((string) $day, 2, '0', STR_PAD_LEFT)] ?? 0)])
            ->all();

        return view('developer.dashboard', compact('gross', 'net', 'sales', 'downloads', 'recent', 'series'));
    }
}
