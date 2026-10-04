<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IapOwnership extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'iap_id', 'transaksi_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function iap(): BelongsTo
    {
        return $this->belongsTo(Iap::class);
    }

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class);
    }
}
