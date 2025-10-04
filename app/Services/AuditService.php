<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditService
{
    /**
     * Log an audit event.
     */
    public function log(
        string $eventType,
        ?int $userId = null,
        ?array $metadata = null,
        ?Request $request = null
    ): void {
        $request = $request ?? request();

        AuditLog::create([
            'user_id' => $userId ?? auth()->id(),
            'event_type' => $eventType,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Log user activity.
     */
    public function logUserActivity(User $user, string $activity, array $metadata = []): void
    {
        $this->log($activity, $user->id, $metadata);
    }

    /**
     * Log security event.
     */
    public function logSecurityEvent(string $event, array $metadata = []): void
    {
        $this->log("security:{$event}", null, $metadata);
    }
}