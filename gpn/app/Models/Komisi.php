<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Komisi extends Model
{
    /** @use HasFactory<\Database\Factories\KomisiFactory> */
    use HasFactory;

    protected $fillable = ['admin_id', 'komisi_persentase', 'komisi_berlaku_mulai'];

    protected function casts(): array
    {
        return [
            'komisi_persentase' => 'decimal:2',
            'komisi_berlaku_mulai' => 'date',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function laporan(): HasMany
    {
        return $this->hasMany(Laporan::class);
    }
}
