<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    protected $fillable = [
        'name',
        'code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the directorates for this region.
     */
    public function directorates(): HasMany
    {
        return $this->hasMany(Directorate::class);
    }

    /**
     * Get active directorates for this region.
     */
    public function activeDirectorates(): HasMany
    {
        return $this->directorates()->where('is_active', true);
    }

    /**
     * Scope to get only active regions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
