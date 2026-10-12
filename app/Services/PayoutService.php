<?php

namespace App\Services;

use App\Models\Developer;
use App\Models\KomisiTransaksi;
use App\Models\Payout;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PayoutService
{
    public function unpaidRows(Developer $developer): Collection
    {
        return KomisiTransaksi::whereNull('payout_id')
            ->whereHas('transaksi.game', fn ($q) => $q->where('developer_id', $developer->id))
            ->with('transaksi')
            ->get();
    }

    public function unpaidBalance(Developer $developer): string
    {
        return (string) $this->unpaidRows($developer)->sum(fn ($row) => (float) $row->developer_amount);
    }

    public function process(Developer $developer, User $admin): ?Payout // SRS 3.1.4.3
    {
        return DB::transaction(function () use ($developer, $admin) {
            $rows = $this->unpaidRows($developer);
            if ($rows->isEmpty()) {
                return null;
            }

            $payout = Payout::create([
                'developer_id' => $developer->id,
                'admin_id' => $admin->id,
                'period' => now()->format('Y-m'),
                'gross' => (string) $rows->sum(fn ($r) => (float) $r->transaksi->total),
                'commission' => (string) $rows->sum(fn ($r) => (float) $r->amount),
                'net' => (string) $rows->sum(fn ($r) => (float) $r->developer_amount),
                'status' => 'processed',
            ]);

            KomisiTransaksi::whereIn('id', $rows->pluck('id'))->update(['payout_id' => $payout->id]);

            return $payout;
        });
    }
}
