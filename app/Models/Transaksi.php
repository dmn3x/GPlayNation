<?php

namespace App\Models;

use App\Enums\TransaksiStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Transaksi extends Model
{
    use HasFactory;

    protected $fillable = ['order_id', 'user_id', 'game_id', 'iap_id', 'type', 'total', 'status'];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'status' => TransaksiStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    public function komisiTransaksi(): HasOne
    {
        return $this->hasOne(KomisiTransaksi::class);
    }

    public static function generateOrderId(): string
    {
        do {
            $id = 'GPN-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (static::where('order_id', $id)->exists());

        return $id;
    }

    public function isFinal(): bool
    {
        return $this->status !== TransaksiStatus::Pending;
    }
}
