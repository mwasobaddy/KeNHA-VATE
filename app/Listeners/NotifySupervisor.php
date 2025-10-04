<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\SupervisorApprovalRequested;
use App\Services\NotificationService;

class NotifySupervisor
{
    /**
     * Create the event listener.
     */
    public function __construct(private readonly NotificationService $notificationService) {}

    /**
     * Handle the event.
     */
    public function handle(SupervisorApprovalRequested $event): void
    {
        // This is already handled in StaffService::requestSupervisorApproval()
        // But we can add additional logic here if needed
    }
}
