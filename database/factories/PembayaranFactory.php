<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Pembayaran;
use App\Models\Transaksi;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Pembayaran>
 */
class PembayaranFactory extends Factory
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
            'method' => PaymentMethod::EWallet,
            'amount' => '75000.00',
            'reference_no' => 'REF-'.strtoupper(Str::random(10)),
            'payload' => [],
            'paid_at' => now(),
        ];
    }
}
