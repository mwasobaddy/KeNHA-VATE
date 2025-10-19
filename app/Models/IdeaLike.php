<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdeaLike extends Model
{
    protected $fillable = [
        'user_id',
        'idea_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who liked the idea
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the idea that was liked
     */
    public function idea(): BelongsTo
    {
        return $this->belongsTo(Idea::class);
    }
}