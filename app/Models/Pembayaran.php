<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pembayaran extends Model
{
    use HasFactory;

    protected $fillable = ['transaksi_id', 'method', 'amount', 'reference_no', 'payload', 'paid_at'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'method' => PaymentMethod::class,
            'payload' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class);
    }
}
