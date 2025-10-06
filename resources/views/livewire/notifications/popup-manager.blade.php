<?php

use Livewire\Volt\Component;

new class extends Component {
    public array $notifications = [];

    protected $listeners = [
        'showPopup' => 'showPopup',
        'showSuccess' => 'showSuccess',
        'showError' => 'showError',
        'showWarning' => 'showWarning',
        'showInfo' => 'showInfo',
    ];

    public function mount(): void
    {
        // Check for popup notifications in session (from login events, etc.)
        $popupNotifications = session('popup_notifications', []);
        if (!empty($popupNotifications)) {
            foreach ($popupNotifications as $notification) {
                $this->notifications[] = [
                    'id' => uniqid('popup-'),
                    'type' => $notification['type'] ?? 'info',
                    'title' => $notification['title'] ?? '',
                    'message' => $notification['message'] ?? '',
                    'duration' => $notification['duration'] ?? 5000,
                ];
            }
            // Clear the session notifications after displaying them
            session()->forget('popup_notifications');
        }
    }

    /**
     * Show a popup notification.
     */
    public function showPopup(
        string $type,
        string $title,
        string $message,
        int $duration = 5000
    ): void {
        $this->notifications[] = [
            'id' => uniqid('popup-'),
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'duration' => $duration,
        ];

        // Auto-remove notification after duration + animation time
        $this->dispatch('notification-added', id: end($this->notifications)['id']);
    }

    /**
     * Show a success popup notification.
     */
    public function showSuccess(string $title, string $message, int $duration = 5000): void
    {
        $this->showPopup('success', $title, $message, $duration);
    }

    /**
     * Show an error popup notification.
     */
    public function showError(string $title, string $message, int $duration = 5000): void
    {
        $this->showPopup('error', $title, $message, $duration);
    }

    /**
     * Show a warning popup notification.
     */
    public function showWarning(string $title, string $message, int $duration = 5000): void
    {
        $this->showPopup('warning', $title, $message, $duration);
    }

    /**
     * Show an info popup notification.
     */
    public function showInfo(string $title, string $message, int $duration = 5000): void
    {
        $this->showPopup('info', $title, $message, $duration);
    }

    /**
     * Remove a notification.
     */
    public function removeNotification(string $id): void
    {
        $this->notifications = array_filter(
            $this->notifications,
            fn($notification) => $notification['id'] !== $id
        );
    }
}; ?>

<div class="fixed top-0 right-0 z-50 pointer-events-none">
    @foreach($notifications as $notification)
        <x-notifications.popup
            :type="$notification['type']"
            :title="$notification['title']"
            :message="$notification['message']"
            :duration="$notification['duration']"
            :id="$notification['id']"
            class="pointer-events-auto"
        />
    @endforeach
</div>