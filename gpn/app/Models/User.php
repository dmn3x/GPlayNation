<?php

namespace App\Models;

use App\Enums\Role;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    protected $fillable = ['name', 'email', 'password', 'role', 'status', 'avatar_path', 'rejection_reason'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'role' => Role::class, 'status' => UserStatus::class];
    }

    public function developer(): HasOne
    {
        return $this->hasOne(Developer::class);
    }

    public function libraries(): HasMany
    {
        return $this->hasMany(Library::class);
    }

    public function iapOwnerships(): HasMany
    {
        return $this->hasMany(IapOwnership::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function transaksis(): HasMany
    {
        return $this->hasMany(Transaksi::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isDeveloper(): bool
    {
        return $this->role === Role::Developer;
    }

    public function isVerifiedDeveloper(): bool
    {
        return $this->isDeveloper() && $this->status === UserStatus::Active;
    }
}
