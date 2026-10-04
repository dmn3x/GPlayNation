<?php

namespace App\Models;

use App\Enums\LibrarySource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Library extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'game_id', 'transaksi_id', 'source', 'is_showcased'];

    protected function casts(): array
    {
        return [
            'source' => LibrarySource::class,
            'is_showcased' => 'boolean',
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

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class);
    }
}
