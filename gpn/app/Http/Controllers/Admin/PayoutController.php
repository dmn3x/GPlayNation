<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Developer;
use App\Models\Payout;
use App\Services\PayoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayoutController extends Controller
{
    public function index(PayoutService $payouts): View // SRS 3.1.4.3
    {
        $developers = Developer::with('user')
            ->whereHas('games.transaksis.komisiTransaksi', fn ($q) => $q->whereNull('payout_id'))
            ->get()
            ->map(fn (Developer $dev) => [
                'developer' => $dev,
                'balance' => $payouts->unpaidBalance($dev),
            ]);

        return view('admin.payouts', [
            'balances' => $developers,
            'payouts' => Payout::with('developer')->latest()->take(20)->get(),
        ]);
    }

    public function store(Request $request, Developer $developer, PayoutService $payouts): RedirectResponse
    {
        $payout = $payouts->process($developer, $request->user());

        if (! $payout) {
            return back()->with('error', 'No payable balance for this developer.');
        }

        return back()->with('success', 'Payout processed: ' . \App\Support\Money::format($payout->net) . '.');
    }
}
