<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(): View // SRS 3.1.3.4
    {
        return view('storefront.transactions.index', [
            'transaksis' => auth()->user()->transaksis()->with('game', 'iap')->latest()->paginate(15),
        ]);
    }

    public function show(Transaksi $transaksi): View // SRS 3.1.3.4
    {
        abort_unless($transaksi->user_id === auth()->id(), 403);

        $transaksi->load(['pembayaran', 'game', 'iap']);

        return view('storefront.transactions.show', ['transaksi' => $transaksi]);
    }
}
