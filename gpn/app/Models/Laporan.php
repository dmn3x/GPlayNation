<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Laporan extends Model
{
    /** @use HasFactory<\Database\Factories\LaporanFactory> */
    use HasFactory;

    protected $fillable = [
        'developer_id', 'komisi_id', 'laporan_periode',
        'laporan_total_penjualan', 'laporan_jumlah_transaksi',
    ];

    protected function casts(): array
    {
        return [
            'laporan_total_penjualan' => 'decimal:2',
            'laporan_jumlah_transaksi' => 'integer',
        ];
    }

    public function developer(): BelongsTo
    {
        return $this->belongsTo(Developer::class);
    }

    public function komisi(): BelongsTo
    {
        return $this->belongsTo(Komisi::class);
    }
}
