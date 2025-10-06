<?php

use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public $notifications;
    public bool $showAll = false;
    public int $unreadCount = 0;
    public bool $loading = false;

    public function mount(): void
    {
        $this->notifications = collect();
        if (Auth::check()) {
            $this->loadNotifications();
        }
    }

    public function loadNotifications(): void
    {
        $user = Auth::user();
        if ($user && $user->id) {
            $query = $user->notifications()->orderBy('created_at', 'desc');

            if (!$this->showAll) {
                $query->limit(10);
            }

            $this->notifications = $query->get();
            $this->unreadCount = $user->notifications()->unread()->count();
        } else {
            $this->notifications = collect();
            $this->unreadCount = 0;
        }
    }

    public function markAsRead($notificationId): void
    {
        $notificationService = app(NotificationService::class);
        $notificationService->markAsRead($notificationId);

        $this->loadNotifications();
    }

    public function markAllAsRead(): void
    {
        try {
            $this->loading = true;
            
            $user = Auth::user();
            if ($user) {
                $updated = $user->notifications()->unread()->update(['read_at' => now()]);
                
                if ($updated > 0) {
                    // Small delay to ensure database operation completes
                    usleep(1000);
                    $this->loadNotifications();
                    $this->dispatch('notifications-updated');
                    
                    // Show success message
                    $this->dispatch('showSuccess', 'Success', 'All notifications marked as read.');
                }
            }
        } catch (\Exception $e) {
            // Log the error
            \Log::error('Error marking notifications as read', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            // Show error message
            $this->dispatch('showError', 'Error', 'Failed to mark notifications as read. Please try again.');
        } finally {
            $this->loading = false;
        }
    }

    public function toggleShowAll(): void
    {
        $this->showAll = !$this->showAll;
        $this->loadNotifications();
    }
}; ?>

@if($notifications->count() > 0)
<div class="space-y-4" wire:key="notifications-container-{{ auth()->id() }}">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100">
            {{ __('Notifications') }}
            @if($unreadCount > 0)
                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                    {{ $unreadCount }} {{ __('unread') }}
                </span>
            @endif
        </h3>

        <div class="flex items-center gap-2">
            @if($unreadCount > 0)
                <flux:button
                    variant="ghost"
                    size="sm"
                    wire:click="markAllAsRead"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50 cursor-not-allowed"
                    :disabled="$loading"
                >
                    <span wire:loading.remove>{{ __('Mark all read') }}</span>
                    <span wire:loading>Marking...</span>
                </flux:button>
            @endif

            <flux:button
                variant="ghost"
                size="sm"
                wire:click="toggleShowAll"
            >
                {{ $showAll ? __('Show less') : __('Show all') }}
            </flux:button>
        </div>
    </div>

    <!-- Notifications List -->
    <div class="space-y-3">
        @foreach($notifications as $notification)
            <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 {{ $notification->isRead() ? 'opacity-75' : 'border-l-4 border-l-blue-500' }}"
                 wire:key="notification-{{ $notification->id }}">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <h4 class="font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $notification->title }}
                            </h4>

                            @if($notification->type === 'success')
                                <flux:icon name="check-circle" class="w-4 h-4 text-green-500" />
                            @elseif($notification->type === 'error')
                                <flux:icon name="x-circle" class="w-4 h-4 text-red-500" />
                            @elseif($notification->type === 'warning')
                                <flux:icon name="exclamation-triangle" class="w-4 h-4 text-yellow-500" />
                            @else
                                <flux:icon name="information-circle" class="w-4 h-4 text-blue-500" />
                            @endif
                        </div>

                        <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-2">
                            {{ $notification->message }}
                        </p>

                        <p class="text-xs text-zinc-500 dark:text-zinc-500">
                            {{ $notification->created_at->diffForHumans() }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2 ml-4">
                        @if($notification->action_url)
                            <flux:link
                                :href="$notification->action_url"
                                class="text-sm text-blue-600 hover:text-blue-500"
                            >
                                {{ __('View') }}
                            </flux:link>
                        @endif

                        @if(!$notification->isRead())
                            <flux:button
                                variant="ghost"
                                size="sm"
                                wire:click="markAsRead({{ $notification->id }})"
                            >
                                <flux:icon name="check" class="w-4 h-4" />
                            </flux:button>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if(!$showAll && $notifications->count() >= 10)
        <div class="text-center">
            <flux:button
                variant="ghost"
                wire:click="toggleShowAll"
            >
                {{ __('Load more notifications') }}
            </flux:button>
        </div>
    @endif
</div>
@else
<div class="text-center py-8" wire:key="no-notifications-{{ auth()->id() }}">
    <flux:icon name="bell" class="w-12 h-12 text-zinc-400 mx-auto mb-4" />
    <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 mb-2">
        {{ __('No notifications') }}
    </h3>
    <p class="text-sm text-zinc-600 dark:text-zinc-400">
        {{ __('You\'re all caught up! Check back later for updates.') }}
    </p>
</div>
@endif