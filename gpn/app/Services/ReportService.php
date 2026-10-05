<?php

namespace App\Services;

use App\Models\Developer;
use App\Models\Download;
use App\Models\KomisiTransaksi;
use App\Models\Pembayaran;
use App\Models\Transaksi;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ReportService
{
    private function settledQuery(Developer $dev, CarbonInterface $month, ?int $gameId)
    {
        return Transaksi::where('status', 'success')
            ->whereHas('game', fn ($q) => $q->where('developer_id', $dev->id))
            ->when($gameId, fn ($q) => $q->where('game_id', $gameId))
            ->whereHas('pembayaran', fn ($q) => $q->whereBetween('paid_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()]));
    }

    public function developerRows(Developer $dev, CarbonInterface $month, ?int $gameId): LengthAwarePaginator
    {
        return $this->settledQuery($dev, $month, $gameId)
            ->with('game', 'user', 'pembayaran', 'komisiTransaksi')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
    }

    public function developerKpis(Developer $dev, CarbonInterface $month, ?int $gameId): array
    {
        $rows = $this->settledQuery($dev, $month, $gameId)->with('komisiTransaksi', 'game')->get();
        $gameIds = $dev->games()->when($gameId, fn ($q) => $q->where('id', $gameId))->pluck('id');

        return [
            'gross' => (string) $rows->sum(fn ($t) => (float) $t->total),
            'commission' => (string) $rows->sum(fn ($t) => (float) ($t->komisiTransaksi->amount ?? 0)),
            'net' => (string) $rows->sum(fn ($t) => (float) ($t->komisiTransaksi->developer_amount ?? 0)),
            'units' => $rows->count(),
            'downloads' => Download::whereIn('game_id', $gameIds)
                ->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
                ->count(),
        ];
    }

    public function developerSeries(Developer $dev, CarbonInterface $month, ?int $gameId): array
    {
        $daily = Pembayaran::whereHas('transaksi', fn ($q) => $q->whereHas('game', fn ($g) => $g->where('developer_id', $dev->id))->when($gameId, fn ($q) => $q->where('game_id', $gameId)))
            ->whereBetween('paid_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->selectRaw('date(paid_at) as d, sum(amount) as s')
            ->groupBy('d')
            ->pluck('s', 'd');

        return collect(range(1, $month->daysInMonth))->map(fn ($day) => [
            'label' => (string) $day,
            'value' => (float) ($daily[$month->format('Y-m-') . str_pad((string) $day, 2, '0', STR_PAD_LEFT)] ?? 0),
        ])->all();
    }

    public function adminTransactions(array $filters): LengthAwarePaginator // SRS 3.1.5.3
    {
        $month = $filters['month'] ?? null; // 'Y-m' or null
        return Transaksi::with('user', 'game', 'pembayaran')
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($month, function ($q) use ($month) {
                $m = Carbon::parse($month . '-01'); // never createFromFormat('Y-m') — fills with current day/time
                $q->where(function ($qq) use ($m) {
                    $qq->whereHas('pembayaran', fn ($p) => $p->whereBetween('paid_at', [$m->copy()->startOfMonth(), $m->copy()->endOfMonth()]))
                       ->orWhere(fn ($n) => $n->where('status', '!=', 'success')->whereBetween('transaksis.created_at', [$m->copy()->startOfMonth(), $m->copy()->endOfMonth()]));
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();
    }

    public function adminCommission(CarbonInterface $month): Collection // SRS 3.1.5.4 — per developer
    {
        return KomisiTransaksi::whereHas('transaksi.pembayaran', fn ($q) => $q->whereBetween('paid_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()]))
            ->with('transaksi.game.developer')
            ->get()
            ->groupBy(fn ($row) => $row->transaksi->game->developer->studio_name)
            ->map(fn ($rows) => [
                'gross' => (string) $rows->sum(fn ($r) => (float) $r->transaksi->total),
                'commission' => (string) $rows->sum(fn ($r) => (float) $r->amount),
                'net' => (string) $rows->sum(fn ($r) => (float) $r->developer_amount),
                'count' => $rows->count(),
            ]);
    }
}
