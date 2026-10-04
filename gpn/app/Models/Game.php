<?php

namespace App\Models;

use App\Enums\GameStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    use HasFactory;

    public const GENRES = ['Action', 'Adventure', 'RPG', 'Strategy', 'Simulation', 'Puzzle', 'Horror', 'Racing', 'Sports', 'Casual'];

    protected $fillable = ['developer_id', 'name', 'slug', 'description', 'price', 'genre', 'cover_path', 'file_path', 'trailer_url', 'status', 'rejection_feedback'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'status' => GameStatus::class,
        ];
    }

    public function developer(): BelongsTo
    {
        return $this->belongsTo(Developer::class);
    }

    public function screenshots(): HasMany
    {
        return $this->hasMany(GameScreenshot::class)->orderBy('sort');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function iaps(): HasMany
    {
        return $this->hasMany(Iap::class);
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }

    public function transaksis(): HasMany
    {
        return $this->hasMany(Transaksi::class);
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(Download::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', GameStatus::Published);
    }

    public function isFree(): bool
    {
        return bccomp($this->price, '0', 2) === 0;
    }

    public function activePromotion(): ?Promotion
    {
        return $this->promotions()
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->latest('starts_at')
            ->first();
    }

    public function effectivePrice(): string
    {
        $promo = $this->activePromotion();
        if (! $promo) {
            return $this->price;
        }

        return bcmul($this->price, bcdiv((string) (100 - $promo->discount_percent), '100', 4), 2);
    }

    public function ownedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->hasOne(Library::class)->where('user_id', $user->id)->exists()
            || $this->isFree();
    }
}
