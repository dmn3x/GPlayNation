<?php

namespace App\Models;

use App\Enums\IapType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Iap extends Model
{
    use HasFactory;

    protected $fillable = ['game_id', 'name', 'description', 'price', 'type', 'is_active'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'type' => IapType::class,
            'is_active' => 'boolean',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
