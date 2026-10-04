<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KomisiTransaksi extends Model
{
    /** @use HasFactory<\Database\Factories\KomisiTransaksiFactory> */
    use HasFactory;

    protected $table = 'komisi_transaksi';

    protected $fillable = ['transaksi_id', 'komisi_id', 'persentase', 'nominal'];

    protected function casts(): array
    {
        return [
            'persentase' => 'decimal:2',
            'nominal' => 'decimal:2',
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
}
