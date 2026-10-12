<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payout extends Model
{
    use HasFactory;

    protected $fillable = ['developer_id', 'admin_id', 'period', 'gross', 'commission', 'net', 'status'];

    protected function casts(): array
    {
        return [
            'gross' => 'decimal:2',
            'commission' => 'decimal:2',
            'net' => 'decimal:2',
        ];
    }

    public function developer(): BelongsTo
    {
        return $this->belongsTo(Developer::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function komisiTransaksis(): HasMany
    {
        return $this->hasMany(KomisiTransaksi::class);
    }
}
