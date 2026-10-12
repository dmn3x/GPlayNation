<?php

namespace App\Http\Controllers;

use App\Enums\TransaksiStatus;
use App\Models\Game;
use App\Models\Iap;
use App\Models\IapOwnership;
use App\Models\Library;
use App\Models\Transaksi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function store(string $type, int $id): RedirectResponse // SRS 3.1.3.1/3.1.3.2
    {
        $user = auth()->user();
        abort_unless(in_array($type, ['game', 'iap']), 404);

        if ($type === 'game') {
            $game = Game::published()->findOrFail($id);
            if ($game->isFree()) {
                return redirect()->route('games.download', $game->slug);
            }
            // NB: check the Library table directly — Game::ownedBy() counts free games as owned.
            if (Library::where('user_id', $user->id)->where('game_id', $game->id)->exists()) {
                return redirect()->route('store.show', $game->slug)->with('error', 'You already own this game.');
            }
            $iap = null;
            $total = $game->effectivePrice();
        } else {
            $iap = Iap::where('is_active', true)->findOrFail($id);
            $game = $iap->game;
            $ownsBase = Library::where('user_id', $user->id)->where('game_id', $game->id)->exists();
            if (! $ownsBase && ! $game->isFree()) {
                return redirect()->route('store.show', $game->slug)->with('error', 'You need the base game before buying add-ons.');
            }
            if (IapOwnership::where('user_id', $user->id)->where('iap_id', $iap->id)->exists()) {
                return redirect()->route('store.show', $game->slug)->with('error', 'You already own this item.');
            }
            $total = $iap->price;
        }

        $trx = Transaksi::create([
            'order_id' => Transaksi::generateOrderId(),
            'user_id' => $user->id,
            'game_id' => $game->id,
            'iap_id' => $iap?->id,
            'type' => $type,
            'total' => $total,
            'status' => TransaksiStatus::Pending,
        ]);

        return redirect()->route('checkout.summary', $trx->order_id);
    }

    public function summary(Transaksi $transaksi): View
    {
        abort_unless($transaksi->user_id === auth()->id(), 403);

        return view('checkout.summary', ['trx' => $transaksi->load('game.developer', 'iap')]);
    }

    public function pay(Request $request, Transaksi $transaksi): RedirectResponse
    {
        abort_unless($transaksi->user_id === auth()->id(), 403);
        $request->validate(['agreement' => ['accepted']]);

        $method = $request->input('method', 'e_wallet');
        if (! in_array($method, ['e_wallet', 'virtual_account', 'credit_card'], true)) {
            $method = 'e_wallet';
        }
        session()->put('gateway_method_'.$transaksi->order_id, $method);

        return redirect()->route('gateway.show', $transaksi->order_id);
    }

    public function cancel(Transaksi $transaksi): RedirectResponse
    {
        abort_unless($transaksi->user_id === auth()->id(), 403);

        return redirect()->route('store.show', $transaksi->game->slug)->with('success', 'Order cancelled. You can buy it anytime.');
    }

    public function result(Transaksi $transaksi): View
    {
        abort_unless($transaksi->user_id === auth()->id(), 403);

        return view('checkout.result', ['trx' => $transaksi->load('game', 'iap', 'pembayaran')]);
    }
}
