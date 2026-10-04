<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Komisi;
use App\Models\KomisiTransaksi;
use App\Models\Library;
use App\Models\Payout;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * SRS 3.1.4 — Modul Administrasi dan Komisi; SRS 3.1.5.3–3.1.5.5 — pelaporan admin.
 */
class AdminController extends Controller
{
    /** SRS 3.1.1.4 Verifikasi Akun */
    public function dashboard(): View
    {
        return view('admin.dashboard', [
            'pendingDevelopers' => User::where('role', 'developer')->where('status', 'pending')->count(),
            'pendingGames' => Game::where('game_status', 'pending_review')->count(),
            'transactions' => Transaksi::count(),
            'gross' => (float) Transaksi::where('transaksi_status', 'settlement')->sum('transaksi_total_harga'),
        ]);
    }

    public function users(): View
    {
        return view('admin.users', [
            'users' => User::where('role', 'developer')->orderBy('status')->paginate(20),
        ]);
    }

    /** SRS 3.1.1.4 — approve or reject a developer account */
    public function verifyUser(Request $request, User $user)
    {
        $data = $request->validate([
            'aksi' => ['required', 'in:setujui,tolak'],
            'alasan' => ['nullable', 'required_if:aksi,tolak', 'string', 'max:500'],
        ]);

        $user->update([
            'status' => $data['aksi'] === 'setujui' ? 'active' : 'rejected',
        ]);

        return back()->with('status', $data['aksi'] === 'setujui'
            ? "Akun {$user->pengguna_nama} disetujui."
            : "Akun {$user->pengguna_nama} ditolak.");
    }

    /** SRS 3.1.4.1 Moderasi Game — activity diagram 3-2.3 */
    public function moderation(): View
    {
        return view('admin.moderation', [
            'games' => Game::whereIn('game_status', ['pending_review', 'rejected'])
                ->with('developer.user')
                ->latest()
                ->paginate(20),
        ]);
    }

    public function moderateGame(Request $request, Game $game)
    {
        $data = $request->validate([
            'aksi' => ['required', 'in:setujui,tolak'],
            'catatan' => ['nullable', 'required_if:aksi,tolak', 'string', 'max:500'],
        ]);

        $game->update([
            'game_status' => $data['aksi'] === 'setujui' ? 'published' : 'rejected',
        ]);

        return back()->with('status', $data['aksi'] === 'setujui'
            ? "{$game->game_nama} dipublikasikan."
            : "{$game->game_nama} ditolak dan umpan balik dikirim ke developer.");
    }

    /** SRS 3.1.4.2 Kelola Komisi — F-08 */
    public function komisi(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->validate([
                'komisi_persentase' => ['required', 'numeric', 'min:0', 'max:100'],
                'komisi_berlaku_mulai' => ['required', 'date'],
            ]);

            // A new schedule closes the previous one rather than mutating history:
            // 3.2.4 / 3.1.3.3 require settled commissions to stay immutable.
            Komisi::create($data + ['admin_id' => $request->user()->id]);

            return back()->with('status', 'Pengaturan komisi disimpan.');
        }

        return view('admin.komisi', [
            'komisi' => Komisi::with('admin')->orderByDesc('komisi_berlaku_mulai')->paginate(20),
            'terikon' => Transaksi::whereNotNull('iap_id')->where('transaksi_status', 'settlement')->count(),
        ]);
    }

    /** SRS 3.1.4.3 Kelola Payout Komisi */
    public function payouts(Request $request)
    {
        $periode = $request->string('periode')->toString() ?: now()->format('Y-m');

        $rows = User::where('role', 'developer')
            ->whereHas('developer')
            ->with('developer')
            ->get()
            ->map(fn ($u) => [
                'developer' => $u->developer,
                'pembelian' => $this->settledRevenue($u->developer->id, $periode),
                'komisi' => $this->settledCommission($u->developer->id, $periode),
                'payout' => Payout::where('developer_id', $u->developer->id)->where('periode', $periode)->first(),
            ])
            ->filter(fn ($r) => $r['pembelian'] > 0);

        return view('admin.payouts', [
            'rows' => $rows,
            'periode' => $periode,
        ]);
    }

    public function processPayout(Request $request, User $user)
    {
        $developer = $user->developer;
        abort_if($developer === null, 404);

        $data = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
        ]);

        $gross = $this->settledRevenue($developer->id, $data['periode']);

        // 3.1.4.3 alternate flow: nothing to pay out.
        if ($gross <= 0) {
            return back()->withErrors(['payout' => 'Payout belum dapat diproses: tidak ada pendapatan pada periode ini.']);
        }

        $komisi = $this->settledCommission($developer->id, $data['periode']);

        Payout::updateOrCreate(
            ['developer_id' => $developer->id, 'periode' => $data['periode']],
            [
                'total_komisi' => $komisi,
                'total_bersih' => max(0, $gross - $komisi),
                'status' => 'diproses',
                'diproses_pada' => now(),
            ]
        );

        return back()->with('status', "Payout {$developer->nama_studio} berhasil diproses.");
    }

    /** Settled gross revenue for one developer in a period. */
    private function settledRevenue(int $developerId, string $periode): float
    {
        return (float) Transaksi::where('transaksi_status', 'settlement')
            ->whereBetween('transaksi_tanggal', $this->periodBounds($periode))
            ->whereIn('game_id', $this->gameIds($developerId))
            ->sum('transaksi_total_harga');
    }

    /** Commission accrued on that developer's settled transactions. */
    private function settledCommission(int $developerId, string $periode): float
    {
        return (float) KomisiTransaksi::whereIn('transaksi_id', function ($q) use ($developerId, $periode) {
            $q->select('id')->from('transaksis')
                ->where('transaksi_status', 'settlement')
                ->whereBetween('transaksi_tanggal', $this->periodBounds($periode))
                ->whereIn('game_id', $this->gameIds($developerId));
        })->sum('nominal');
    }

    /** @return array{0: \Illuminate\Support\Carbon, 1: \Illuminate\Support\Carbon} */
    private function periodBounds(string $periode): array
    {
        $start = Carbon::createFromFormat('Y-m-d', $periode.'-01')->startOfMonth();

        return [$start, $start->copy()->endOfMonth()];
    }

    private function gameIds(int $developerId): array
    {
        return Game::where('developer_id', $developerId)->pluck('id')->all();
    }

    /** F-10 Pelaporan Transaksi Platform — 3.1.5.3 & 3.1.5.4 */
    public function reports(Request $request)
    {
        $status = $request->string('status')->toString();

        $trx = Transaksi::with(['pengguna', 'game', 'komisi'])
            ->when($status !== 'semua', fn ($q) => $q->where('transaksi_status', $status))
            ->latest('transaksi_tanggal');

        return view('admin.reports', [
            'trx' => $trx->paginate(20),
            'status' => $status,
            'totalTransaksi' => Transaksi::count(),
            'totalPendapatan' => (float) Transaksi::where('transaksi_status', 'settlement')->sum('transaksi_total_harga'),
            'totalKomisi' => (float) KomisiTransaksi::sum('nominal'),
        ]);
    }

    /**
     * SRS 3.1.3.3 Menyimpan Status Pembayaran — Midtrans webhook receiver
     * (activity diagram 3-2.4).
     *
     * Midtrans signs each notification with
     *   sha512(order_id + status_code + gross_amount + server_key)
     * compared against the payload's signature_key. This route is exempt from
     * CSRF because the caller is Midtrans's servers, so that signature is the
     * only thing establishing authenticity — an unsigned POST would otherwise let
     * anyone mark any transaction as paid.
     */
    public function paymentWebhook(Request $request)
    {
        $data = $request->validate([
            'order_id' => ['required', 'string'],
            'transaction_status' => ['required', 'string'],
            'status_code' => ['required', 'string'],
            'gross_amount' => ['required', 'string'],
            'signature_key' => ['required', 'string'],
            'payment_type' => ['nullable', 'string'],
        ]);

        $serverKey = config('services.midtrans.server_key');

        // Fail loudly rather than verifying against an empty key, which would make
        // every notification forgeable.
        if (! $serverKey) {
            Log::error('MIDTRANS_SERVER_KEY is not configured; cannot verify notification signatures.');

            return response()->json(['message' => 'server key not configured'], 503);
        }

        $expected = hash('sha512',
            $data['order_id'].$data['status_code'].$data['gross_amount'].$serverKey);

        if (! hash_equals($expected, $data['signature_key'])) {
            Log::warning('Rejected Midtrans notification: signature mismatch', [
                'order_id' => $data['order_id'],
            ]);

            // 3-2.4: log the exception and halt the process.
            return response()->json(['message' => 'invalid signature'], 403);
        }

        $transaksi = Transaksi::find($data['order_id']);
        if (! $transaksi) {
            return response()->json(['message' => 'order not found'], 404);
        }

        // A settled transaction is immutable: replayed notifications are dropped.
        if ($transaksi->transaksi_status === 'settlement') {
            return response()->json(['message' => 'already settled']);
        }

        $status = match ($data['transaction_status']) {
            'capture', 'settlement' => 'settlement',
            'pending' => 'pending',
            default => 'failed',
        };

        DB::transaction(function () use ($transaksi, $status, $data) {
            $transaksi->update(['transaksi_status' => $status]);

            $transaksi->pembayaran()->updateOrCreate([], [
                'pembayaran_jumlah' => $transaksi->transaksi_total_harga,
                'metode' => $data['payment_type'] ?? null,
                'nomor_ref' => $data['order_id'],
            ]);

            // SRS 3.2.5 — on success the product lands in the user's library.
            if ($status === 'settlement' && $transaksi->game_id) {
                Library::firstOrCreate(
                    ['pengguna_id' => $transaksi->pengguna_id, 'game_id' => $transaksi->game_id],
                    ['jam_main' => 0]
                );

                // F-08: commission is earned at settlement, not at order creation,
                // so abandoned or failed payments never accrue a payable. The rate
                // is snapshotted here to stay immutable if the schedule later changes.
                if (! $transaksi->komisi()->exists()) {
                    $komisi = Komisi::whereDate('komisi_berlaku_mulai', '<=', now()->toDateString())
                        ->orderByDesc('komisi_berlaku_mulai')
                        ->first();

                    if ($komisi) {
                        KomisiTransaksi::create([
                            'transaksi_id' => $transaksi->id,
                            'komisi_id' => $komisi->id,
                            'persentase' => $komisi->komisi_persentase,
                            'nominal' => $transaksi->transaksi_total_harga * $komisi->komisi_persentase / 100,
                        ]);
                    }
                }
            }
        });

        return response()->json(['ok' => true]);
    }
}