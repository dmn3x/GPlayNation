<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Komisi extends Model
{
    use HasFactory;

    protected $fillable = ['admin_id', 'percentage', 'effective_from'];

    protected function casts(): array
    {
        return [
            'percentage' => 'decimal:2',
            'effective_from' => 'date',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
