<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaksi extends Model
{
    /** @use HasFactory<\Database\Factories\TransaksiFactory> */
    use HasFactory;

    protected $fillable = [
        'pengguna_id', 'game_id', 'iap_id', 'transaksi_tanggal',
        'transaksi_total_harga', 'transaksi_status',
    ];

    protected function casts(): array
    {
        return [
            'transaksi_tanggal' => 'datetime',
            'transaksi_total_harga' => 'decimal:2',
        ];
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengguna_id');
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function iap(): BelongsTo
    {
        return $this->belongsTo(Iap::class);
    }

    public function pembayaran(): HasOne
    {
        return $this->hasOne(Pembayaran::class);
    }

    public function komisi(): HasOne
    {
        return $this->hasOne(KomisiTransaksi::class);
    }
}
