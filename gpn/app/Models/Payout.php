<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payout extends Model
{
    /** @use HasFactory<\Database\Factories\PayoutFactory> */
    use HasFactory;

    protected $fillable = ['developer_id', 'periode', 'total_komisi', 'total_bersih', 'status', 'diproses_pada'];

    protected function casts(): array
    {
        return [
            'total_komisi' => 'decimal:2',
            'total_bersih' => 'decimal:2',
            'diproses_pada' => 'datetime',
        ];
    }

    public function developer(): BelongsTo
    {
        return $this->belongsTo(Developer::class);
    }
}
