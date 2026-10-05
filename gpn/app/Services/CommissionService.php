<?php

namespace App\Services;

use App\Models\Komisi;
use App\Models\KomisiTransaksi;
use App\Models\Transaksi;
use App\Models\User;
use Carbon\CarbonInterface;

class CommissionService
{
    public function percentageFor(CarbonInterface $date): string
    {
        // Only rows ALREADY in effect count — a future-dated schedule must never apply early.
        $row = Komisi::whereDate('effective_from', '<=', $date->toDateString())
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();

        return $row?->percentage ?? config('gpn.default_commission');
    }

    public function snapshot(Transaksi $trx): KomisiTransaksi
    {
        $percentage = $this->percentageFor(now());
        $komisi = Komisi::whereDate('effective_from', '<=', today())
            ->orderByDesc('effective_from')->orderByDesc('id')->first()
            ?? Komisi::create(['admin_id' => User::where('role', 'admin')->value('id') ?? 1, 'percentage' => $percentage, 'effective_from' => today()]);
        $amount = bcmul($trx->total, bcdiv($percentage, '100', 4), 2);

        return KomisiTransaksi::create([
            'transaksi_id' => $trx->id,
            'komisi_id' => $komisi->id,
            'percentage' => $percentage,
            'amount' => $amount,
            'developer_amount' => bcsub($trx->total, $amount, 2),
        ]);
    }
}
