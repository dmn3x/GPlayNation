<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KomisiTransaksi extends Model
{
    use HasFactory;

    protected $table = 'komisi_transaksi';

    protected $fillable = ['transaksi_id', 'komisi_id', 'percentage', 'amount', 'developer_amount', 'payout_id'];

    protected function casts(): array
    {
        return [
            'percentage' => 'decimal:2',
            'amount' => 'decimal:2',
            'developer_amount' => 'decimal:2',
        ];
    }

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class);
    }

    public function komisi(): BelongsTo
    {
        return $this->belongsTo(Komisi::class);
    }

    public function payout(): BelongsTo
    {
        return $this->belongsTo(Payout::class);
    }
}
