<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'pengguna_nama',
        'email',
        'password',
        'role',
        'status',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function developer(): HasOne
    {
        return $this->hasOne(Developer::class);
    }

    public function library(): HasMany
    {
        return $this->hasMany(Library::class, 'pengguna_id');
    }

    public function wishlist(): HasMany
    {
        return $this->hasMany(Wishlist::class, 'pengguna_id');
    }

    public function transaksis(): HasMany
    {
        return $this->hasMany(Transaksi::class, 'pengguna_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'pengguna_id');
    }

    public function sudahBeli(Game $game): bool
    {
        return $this->library()->where('game_id', $game->id)->exists()
            || $this->transaksis()
                ->where('game_id', $game->id)
                ->where('transaksi_status', 'settlement')
                ->exists();
    }
}