<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    /** @use HasFactory<\Database\Factories\GameFactory> */
    use HasFactory;

    protected $fillable = [
        'game_nama', 'deskripsi', 'game_harga', 'game_genre', 'kategori', 'game_status',
        'cover', 'screenshots', 'trailer', 'platform', 'rilis', 'rating', 'unduhan', 'sysreq',
    ];

    protected function casts(): array
    {
        return [
            'screenshots' => 'array',
            'sysreq' => 'array',
            'game_harga' => 'decimal:2',
            'rating' => 'integer',
            'unduhan' => 'integer',
            'rilis' => 'date',
        ];
    }

    public function developer(): BelongsTo
    {
        return $this->belongsTo(Developer::class);
    }

    public function iaps(): HasMany
    {
        return $this->hasMany(Iap::class);
    }

    public function scopeTayang($query)
    {
        return $query->where('game_status', 'published');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->game_status) {
            'published' => 'Live',
            'pending_review' => 'Pending Review',
            'rejected' => 'Rejected',
            'archived' => 'Archived',
            default => 'Draft',
        };
    }
}
