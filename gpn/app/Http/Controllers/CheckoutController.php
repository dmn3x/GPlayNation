<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Iap;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * SRS 3.1.3.1 / 3.1.3.2 — Membeli Game & Membeli IAP (activity diagram 3-2.2).
 */
class CheckoutController extends Controller
{
    /** Order summary review step, per the F-05 flow. */
    public function review(Request $request, string $type, int $id): View
    {
        [$item, $harga, $nama] = $this->resolve($type, $id);

        return view('checkout.review', [
            'type' => $type,
            'item' => $item,
            'harga' => $harga,
            'nama' => $nama,
        ]);
    }

    /** SRS 3.1.3.3 — create the transaction, request a Midtrans snap token.
     *
     * Commission is deliberately NOT written here: F-08 accrues it at settlement,
     * so abandoned or failed checkouts never become a payable.
     */
    public function store(Request $request, string $type, int $id): View
    {
        [$item, $harga, $nama] = $this->resolve($type, $id);

        if ($type === 'game' && ! $request->boolean('setuju')) {
            return back()->withErrors(['setuju' => 'Anda harus menyetujui ketentuan sebelum membayar.']);
        }

        $transaksi = Transaksi::create([
            'pengguna_id' => $request->user()->id,
            'game_id' => $type === 'game' ? $id : $item->game_id,
            'iap_id' => $type === 'iap' ? $id : null,
            'transaksi_tanggal' => now(),
            'transaksi_total_harga' => $harga,
            'transaksi_status' => 'pending',
        ]);

        return view('checkout.pending', [
            'transaksi' => $transaksi,
            'nama' => $nama,
            // Real flow returns the Midtrans Snap token and redirects to their
            // checkout popup; the webhook then finalises the status.
            'snapToken' => 'dev-snap-'.$transaksi->id,
        ]);
    }

    /** SRS 3.1.3.4 Melihat Riwayat Transaksi */
    public function history(): View
    {
        return view('checkout.history', [
            'trx' => request()->user()->transaksis()
                ->with(['game', 'iap', 'pembayaran'])
                ->latest('transaksi_tanggal')
                ->paginate(20),
        ]);
    }

    /** @return array{0: mixed, 1: float, 2: string} */
    private function resolve(string $type, int $id): array
    {
        if ($type === 'game') {
            $game = Game::tayang()->findOrFail($id);

            abort_if(request()->user()->sudahBeli($game), 409, 'Anda sudah memiliki game ini.');

            return [$game, (float) $game->game_harga, $game->game_nama];
        }

        abort_if($type !== 'iap', 404);
        $iap = Iap::where('aktif', true)->findOrFail($id);

        return [$iap, (float) $iap->iap_harga, $iap->iap_nama.' — '.$iap->game->game_nama];
    }
}