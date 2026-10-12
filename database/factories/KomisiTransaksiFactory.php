<?php

namespace Database\Factories;

use App\Models\Komisi;
use App\Models\KomisiTransaksi;
use App\Models\Transaksi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KomisiTransaksi>
 */
class KomisiTransaksiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transaksi_id' => Transaksi::factory(),
            'komisi_id' => Komisi::factory(),
            'percentage' => '20.00',
            'amount' => '15000.00',
            'developer_amount' => '60000.00',
            'payout_id' => null,
        ];
    }
}
