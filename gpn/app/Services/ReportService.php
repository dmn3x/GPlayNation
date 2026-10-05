<?php

namespace App\Services;

use App\Models\Developer;
use App\Models\Download;
use App\Models\Pembayaran;
use App\Models\Transaksi;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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
}
