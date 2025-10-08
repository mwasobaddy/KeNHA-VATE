<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'event_type',
        'ip_address',
        'user_agent',
        'metadata',
        'resource_type',
        'resource_id',
    ];

    protected $casts = [
        'metadata' => 'array',
        'resource_id' => 'integer',
    ];

    /**
     * Get the user that owns this audit log entry.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
