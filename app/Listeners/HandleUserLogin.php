<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\UserLoggedIn;
use App\Services\PointService;

class HandleUserLogin
{
    /**
     * Create the event listener.
     */
    public function __construct(private readonly PointService $pointService) {}

    /**
     * Handle the event.
     */
    public function handle(UserLoggedIn $event): void
    {
        // Award points for first login
        if ($event->isFirstLogin && !$this->pointService->hasReceivedFirstLoginBonus($event->user)) {
            $this->pointService->awardPoints(
                $event->user,
                $this->pointService->getFirstLoginPoints(),
                'First login bonus'
            );
        }

        // TODO: Award daily login points if not already awarded today
        // This would require tracking daily logins
    }
}
