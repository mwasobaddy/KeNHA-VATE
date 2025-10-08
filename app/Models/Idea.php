<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Idea extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ideas';

    protected $fillable = [
        'idea_title',
        'slug',
        'thematic_area_id',
        'abstract',
        'problem_statement',
        'proposed_solution',
        'cost_benefit_analysis',
        'declaration_of_interests',
        'original_idea_disclaimer',
        'collaboration_enabled',
        'team_effort',
        'team_members',
        'attachment',
        'attachment_filename',
        'attachment_mime',
        'attachment_size',
        'status',
        'user_id',
    ];

    protected $casts = [
        'original_idea_disclaimer' => 'boolean',
        'collaboration_enabled' => 'boolean',
        'team_effort' => 'boolean',
        'team_members' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function (Idea $idea) {
            if (empty($idea->slug)) {
                $base = Str::slug(substr($idea->idea_title ?: 'idea', 0, 50));
                $slug = $base;
                $counter = 1;
                while (self::where('slug', $slug)->exists()) {
                    $slug = $base . '-' . $counter++;
                }
                $idea->slug = $slug;
            }
        });
    }

    /**
     * Mark idea as draft.
     */
    public function markDraft(): void
    {
        $this->update(['status' => 'draft']);
    }

    /**
     * Mark idea as submitted.
     */
    public function markSubmitted(): void
    {
        $this->update(['status' => 'submitted']);
    }

    /**
     * Return attachment as a base64 data URI for inline previews (UI only)
     */
    public function getAttachmentDataUriAttribute(): ?string
    {
        if (!$this->attachment || !$this->attachment_mime) {
            return null;
        }

        return 'data:' . $this->attachment_mime . ';base64,' . base64_encode($this->attachment);
    }

    /**
     * Get the thematic area that owns the idea.
     */
    public function thematicArea(): BelongsTo
    {
        return $this->belongsTo(ThematicArea::class);
    }

    /**
     * Get the user that owns the idea.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
