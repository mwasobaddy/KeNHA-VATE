<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\UserLoggedIn;
use App\Services\NotificationService;
use App\Services\PointService;

class HandleUserLogin
{
    /**
     * Create the event listener.
     */
    public function __construct(
        private readonly PointService $pointService,
        private readonly NotificationService $notificationService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(UserLoggedIn $event): void
    {
        // Create pending notification from session (e.g., OTP success)
        if (session()->has('pending_notification')) {
            $notification = session('pending_notification');
            $this->notificationService->notify(
                $event->user,
                $notification['type'],
                $notification['title'],
                $notification['message']
            );

            // Also show as popup notification
            $popupNotifications = session('popup_notifications', []);
            $popupNotifications[] = [
                'type' => $notification['type'],
                'title' => $notification['title'],
                'message' => $notification['message'],
            ];
            session(['popup_notifications' => $popupNotifications]);

            session()->forget('pending_notification');
        }

        // Send welcome notification for successful login
        $this->notificationService->success(
            $event->user,
            'Welcome back!',
            'You have successfully logged in to your KENHAVATE account.'
        );

        // Show welcome popup
        $popupNotifications = session('popup_notifications', []);
        $popupNotifications[] = [
            'type' => 'success',
            'title' => 'Welcome back!',
            'message' => 'You have successfully logged in to your KENHAVATE account.',
        ];
        session(['popup_notifications' => $popupNotifications]);

        // Award points for first login
        if ($event->isFirstLogin && !$this->pointService->hasReceivedFirstLoginBonus($event->user)) {
            $this->pointService->awardPoints(
                $event->user,
                $this->pointService->getFirstLoginPoints(),
                'First login bonus'
            );

            // Show first login bonus popup
            $popupNotifications = session('popup_notifications', []);
            $popupNotifications[] = [
                'type' => 'success',
                'title' => 'First Login Bonus!',
                'message' => 'Congratulations! You\'ve earned ' . $this->pointService->getFirstLoginPoints() . ' points for your first login.',
            ];
            session(['popup_notifications' => $popupNotifications]);
        }

        // TODO: Award daily login points if not already awarded today
        // This would require tracking daily logins
    }
}
