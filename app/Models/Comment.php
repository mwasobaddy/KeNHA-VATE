<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'slug',
        'user_id',
        'idea_id',
        'parent_id',
        'content',
        'comment_is_disabled',
        'replies_count',
    ];

    protected $casts = [
        'comment_is_disabled' => 'boolean',
        'replies_count' => 'integer',
    ];

    protected static function booted()
    {
        // Generate unique slug when creating a comment
        static::creating(function (Comment $comment) {
            $comment->slug = $comment->generateUniqueSlug();
        });

        // Update parent comment's replies_count when a reply is created
        static::created(function (Comment $comment) {
            if ($comment->parent_id) {
                $comment->parent->increment('replies_count');
            }
        });

        // Update parent comment's replies_count when a reply is deleted
        static::deleted(function (Comment $comment) {
            if ($comment->parent_id) {
                $comment->parent->decrement('replies_count');
            }
        });

        // Update parent comment's replies_count when a reply is restored
        static::restored(function (Comment $comment) {
            if ($comment->parent_id) {
                $comment->parent->increment('replies_count');
            }
        });
    }

    /**
     * Get the user that owns the comment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the idea that owns the comment.
     */
    public function idea(): BelongsTo
    {
        return $this->belongsTo(Idea::class);
    }

    /**
     * Get the parent comment (for nested comments).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * Get the replies to this comment.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    /**
     * Generate a unique slug for the comment.
     */
    protected function generateUniqueSlug(): string
    {
        do {
            $slug = (string) random_int(1000000000, 9999999999); // 10-digit random number
        } while (static::where('slug', $slug)->exists());

        return $slug;
    }
}
