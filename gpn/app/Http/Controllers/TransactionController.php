<?php

namespace App\Http\Controllers;

use App\Enums\TransaksiStatus;
use App\Models\Transaksi;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(): View // SRS 3.1.3.4
    {
        $user = auth()->user();
        $query = $user->transaksis()->with('game', 'iap', 'pembayaran')->latest();
        if (in_array(request('status'), ['pending', 'success', 'failed', 'expired'], true)) {
            $query->where('status', request('status'));
        }

        return view('storefront.transactions.index', [
            'transaksis' => $query->paginate(15),
            'stats' => [
                'total' => $user->transaksis()->count(),
                'success' => $user->transaksis()->where('status', TransaksiStatus::Success)->count(),
                'spent' => (float) $user->transaksis()->where('status', TransaksiStatus::Success)->sum('total'),
            ],
            'pending' => $user->transaksis()->where('status', TransaksiStatus::Pending)->latest()->first(),
            'latest' => $user->transaksis()->latest()->first(),
        ]);
    }

    public function show(Transaksi $transaksi): View // SRS 3.1.3.4
    {
        abort_unless($transaksi->user_id === auth()->id(), 403);

        $transaksi->load(['pembayaran', 'game', 'iap']);

        return view('storefront.transactions.show', [
            'transaksi' => $transaksi,
            'pending' => auth()->user()->transaksis()->where('status', TransaksiStatus::Pending)->latest()->first(),
        ]);
    }
}
