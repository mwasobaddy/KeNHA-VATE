<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ProfileCompleted;
use App\Services\NotificationService;

class SendWelcomeNotification
{
    /**
     * Create the event listener.
     */
    public function __construct(private readonly NotificationService $notificationService) {}

    /**
     * Handle the event.
     */
    public function handle(ProfileCompleted $event): void
    {
        $this->notificationService->success(
            $event->user,
            'Welcome to KENHAVATE!',
            'Your profile has been completed successfully. You can now access all features of the system.'
        );
    }
}
