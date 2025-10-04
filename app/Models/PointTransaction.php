<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PointTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'points',
        'transaction_type',
        'description',
        'reference_type',
        'reference_id',
    ];

    /**
     * Get the user that owns this point transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the reference model (polymorphic relationship).
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope to get only earned transactions.
     */
    public function scopeEarned($query)
    {
        return $query->where('transaction_type', 'earned');
    }

    /**
     * Scope to get only redeemed transactions.
     */
    public function scopeRedeemed($query)
    {
        return $query->where('transaction_type', 'redeemed');
    }

    /**
     * Check if this is an earned transaction.
     */
    public function isEarned(): bool
    {
        return $this->transaction_type === 'earned';
    }

    /**
     * Check if this is a redeemed transaction.
     */
    public function isRedeemed(): bool
    {
        return $this->transaction_type === 'redeemed';
    }
}
