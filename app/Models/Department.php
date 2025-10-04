<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = [
        'directorate_id',
        'name',
        'code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the directorate that owns this department.
     */
    public function directorate(): BelongsTo
    {
        return $this->belongsTo(Directorate::class);
    }

    /**
     * Get the region through the directorate.
     */
    public function region(): BelongsTo
    {
        return $this->directorate->region();
    }

    /**
     * Get the staff in this department.
     */
    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    /**
     * Scope to get only active departments.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
