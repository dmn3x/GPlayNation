<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use App\Services\Payment\SimulatedGateway;
use App\Services\SettlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GatewayController extends Controller
{
    public function show(Transaksi $transaksi): View
    {
        $this->authorizeOwner($transaksi);

        return view('gateway.show', [
            'trx' => $transaksi->load('game', 'iap'),
            'preferredMethod' => session('gateway_method_'.$transaksi->order_id, 'e_wallet'),
        ]);
    }

    public function complete(Request $request, Transaksi $transaksi, SettlementService $settlement): RedirectResponse
    {
        $this->authorizeOwner($transaksi);
        $data = $request->validate([
            'method' => ['required', 'in:e_wallet,virtual_account,credit_card'],
            'outcome' => ['required', 'in:settlement,deny,expire'],
        ]);

        if (! $transaksi->isFinal()) {
            $settlement->handleNotification(SimulatedGateway::payload($transaksi, $data['outcome'], $data['method']));
        }

        return redirect()->route('checkout.result', $transaksi->order_id);
    }

    private function authorizeOwner(Transaksi $transaksi): void
    {
        abort_unless($transaksi->user_id === auth()->id(), 403);
    }
}
