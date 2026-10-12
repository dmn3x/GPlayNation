<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'body', 'image_path', 'hashtags'];

    protected function casts(): array
    {
        return [
            'hashtags' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(PostVote::class);
    }

    public function getScoreAttribute(): int
    {
        return (int) $this->votes->sum('value');
    }
}
