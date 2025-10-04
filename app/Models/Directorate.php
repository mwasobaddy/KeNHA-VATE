<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Directorate extends Model
{
    protected $fillable = [
        'region_id',
        'name',
        'code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the region that owns this directorate.
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Get the departments for this directorate.
     */
    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    /**
     * Get active departments for this directorate.
     */
    public function activeDepartments(): HasMany
    {
        return $this->departments()->where('is_active', true);
    }

    /**
     * Scope to get only active directorates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
