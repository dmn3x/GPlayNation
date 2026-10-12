<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Developer extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'studio_name', 'portfolio', 'payout_bank', 'payout_account'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }
}
