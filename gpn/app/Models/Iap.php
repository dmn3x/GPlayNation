<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Iap extends Model
{
    /** @use HasFactory<\Database\Factories\IapFactory> */
    use HasFactory;

    protected $table = 'iaps';

    protected $fillable = ['iap_nama', 'deskripsi', 'iap_harga', 'iap_tipe', 'aktif'];

    protected function casts(): array
    {
        return [
            'iap_harga' => 'decimal:2',
            'aktif' => 'boolean',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
