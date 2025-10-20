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
                    usleep(1000);
                    $this->loadNotifications();
                    $this->dispatch('notifications-updated');
                    $this->dispatch('showSuccess', 'Success', 'All notifications marked as read.');
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error marking notifications as read', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
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

<div class="backdrop-blur-lg">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 border border-zinc-200 dark:border-yellow-400 rounded-3xl bg-gradient-to-br from-[#F8EBD5]/20 via-white to-[#F8EBD5] dark:from-zinc-900/20 dark:via-zinc-800 dark:to-zinc-900">

        @if($notifications->count() > 0)
        <div class="space-y-6" wire:key="notifications-container-{{ auth()->id() }}">
            <!-- Header Section with Icon -->
            <div class="mb-8 sm:mb-12 gap-6 flex flex-col">
                <div class="flex flex-row items-start sm:items-center gap-4 sm:gap-6">
                    <!-- Animated Icon Badge -->
                    <div 
                        x-data="{ show: false }" 
                        x-init="setTimeout(() => show = true, 100)"
                        x-show="show"
                        x-transition:enter="transition ease-out duration-500 delay-100"
                        x-transition:enter-start="opacity-0 scale-75 -rotate-12"
                        x-transition:enter-end="opacity-100 scale-100 rotate-0"
                        class="flex-shrink-0"
                    >
                        <div class="relative">
                            <div class="absolute inset-0 bg-[#FFF200]/20 dark:bg-yellow-400/20 rounded-2xl blur-xl"></div>
                            <div class="relative flex items-center justify-center w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-gradient-to-br from-[#FFF200] via-yellow-300 to-yellow-400 dark:from-yellow-400 dark:via-yellow-500 dark:to-yellow-600 shadow-lg">
                                <flux:icon name="bell" class="w-8 h-8 sm:w-10 sm:h-10 text-[#231F20] dark:text-zinc-900" />
                            </div>
                        </div>
                    </div>

                    <!-- Header Text with staggered animation -->
                    <div 
                        class="flex-1"
                        x-data="{ show: false }" 
                        x-init="setTimeout(() => show = true, 200)"
                    >
                        <div 
                            x-show="show"
                            x-transition:enter="transition ease-out duration-700"
                            x-transition:enter-start="opacity-0 translate-x-4"
                            x-transition:enter-end="opacity-100 translate-x-0"
                        >
                            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-[#231F20] dark:text-white tracking-tight">
                                {{ __('Notifications') }}
                            </h1>
                            <p class="mt-2 text-base sm:text-lg text-[#9B9EA4] dark:text-zinc-400">
                                @if($unreadCount > 0)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#FFF200] dark:bg-yellow-500 text-[#231F20] dark:text-zinc-900">
                                        {{ $unreadCount }} {{ __('unread') }}
                                    </span>
                                @else
                                    <span class="text-sm">{{ __('All caught up! You have no unread notifications.') }}</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    <!-- Action Button -->
                    @if($unreadCount > 0)
                        <div class="flex-shrink-0">
                            <flux:button
                                wire:click="markAllAsRead"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50 cursor-not-allowed"
                                :disabled="$loading"
                                variant="primary"
                                class="bg-[#FFF200] hover:bg-yellow-400 text-[#231F20] dark:bg-yellow-500 dark:hover:bg-yellow-600"
                            >
                                <flux:icon name="check-badge" class="w-4 h-4 mr-2" />
                                <span wire:loading.remove>{{ __('Mark all read') }}</span>
                                <span wire:loading class="flex items-center gap-2">
                                    <div class="w-4 h-4 border-2 border-current border-t-transparent rounded-full animate-spin"></div>
                                    {{ __('Marking...') }}
                                </span>
                            </flux:button>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Notifications List -->
            <div class="space-y-4">
                @foreach($notifications as $notification)
                    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 p-6 hover:shadow-xl transition-all duration-300 {{ $notification->isRead() ? '' : 'ring-2 ring-[#FFF200] dark:ring-yellow-500' }}"
                         wire:key="notification-{{ $notification->id }}"
                         x-data="{ expanded: false }"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform scale-95"
                         x-transition:enter-end="opacity-100 transform scale-100">

                        <!-- Unread indicator -->
                        @if(!$notification->isRead())
                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-[#FFF200] to-yellow-400 dark:from-yellow-400 dark:to-yellow-600 rounded-l-2xl"></div>
                        @endif

                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-3 mb-3">
                                    <!-- Notification Icon -->
                                    <div class="flex-shrink-0 p-2 rounded-xl {{ $notification->type === 'success' ? 'bg-green-100 dark:bg-green-900/30' : ($notification->type === 'error' ? 'bg-red-100 dark:bg-red-900/30' : ($notification->type === 'warning' ? 'bg-yellow-100 dark:bg-yellow-900/30' : 'bg-[#F8EBD5] dark:bg-yellow-900/30')) }}">
                                        @if($notification->type === 'success')
                                            <flux:icon name="check-circle" class="w-5 h-5 text-green-600 dark:text-green-400" />
                                        @elseif($notification->type === 'error')
                                            <flux:icon name="x-circle" class="w-5 h-5 text-red-600 dark:text-red-400" />
                                        @elseif($notification->type === 'warning')
                                            <flux:icon name="exclamation-triangle" class="w-5 h-5 text-yellow-600 dark:text-yellow-400" />
                                        @else
                                            <flux:icon name="information-circle" class="w-5 h-5 text-[#231F20] dark:text-yellow-400" />
                                        @endif
                                    </div>

                                    <!-- Title and Status -->
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-semibold text-[#231F20] dark:text-white text-base leading-tight">
                                            {{ $notification->title }}
                                        </h4>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="text-xs text-[#9B9EA4] dark:text-zinc-400 font-medium">
                                                {{ $notification->created_at->diffForHumans() }}
                                            </span>
                                            @if(!$notification->isRead())
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-[#FFF200] text-[#231F20] dark:bg-yellow-500 dark:text-zinc-900">
                                                    {{ __('New') }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Message -->
                                <div class="ml-11">
                                    <p class="text-sm text-[#9B9EA4] dark:text-zinc-400 leading-relaxed">
                                        {{ $notification->message }}
                                    </p>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex items-center gap-2 ml-4 flex-shrink-0">
                                @if($notification->action_url)
                                    <flux:link
                                        :href="$notification->action_url"
                                        class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-[#231F20] dark:text-white bg-[#F8EBD5] hover:bg-[#FFF200] dark:bg-yellow-900/30 dark:hover:bg-yellow-800/50 rounded-lg transition-colors duration-200"
                                    >
                                        <flux:icon name="arrow-right" class="w-4 h-4 mr-1" />
                                        {{ __('View') }}
                                    </flux:link>
                                @endif

                                @if(!$notification->isRead())
                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        wire:click="markAsRead({{ $notification->id }})"
                                        class="p-2 text-[#9B9EA4] hover:text-[#231F20] dark:text-zinc-400 dark:hover:text-white hover:bg-[#F8EBD5] dark:hover:bg-zinc-700 rounded-lg transition-all duration-200"
                                        title="{{ __('Mark as read') }}"
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
                <div class="text-center py-6">
                    <flux:button
                        icon="{{ $showAll ? 'arrow-up' : 'arrow-down' }}"
                        variant="primary"
                        wire:click="toggleShowAll"
                    >
                        {{ __('Load more notifications') }}
                    </flux:button>
                </div>
            @endif
        </div>
        @else
        <!-- Empty State -->
        <div class="text-center py-12 px-6">
            <!-- Animated Icon Badge -->
            <div 
                x-data="{ show: false }" 
                x-init="setTimeout(() => show = true, 100)"
                x-show="show"
                x-transition:enter="transition ease-out duration-500 delay-100"
                x-transition:enter-start="opacity-0 scale-75"
                x-transition:enter-end="opacity-100 scale-100"
                class="inline-block mb-6"
            >
                <div class="relative">
                    <div class="absolute inset-0 bg-[#FFF200]/20 dark:bg-yellow-400/20 rounded-2xl blur-xl"></div>
                    <div class="relative flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-[#FFF200] via-yellow-300 to-yellow-400 dark:from-yellow-400 dark:via-yellow-500 dark:to-yellow-600 shadow-lg">
                        <flux:icon name="bell" class="w-10 h-10 text-[#231F20] dark:text-zinc-900" />
                    </div>
                </div>
            </div>

            <h3 class="text-xl font-bold text-[#231F20] dark:text-white mb-3">
                {{ __('No notifications') }}
            </h3>
            <p class="text-base text-[#9B9EA4] dark:text-zinc-400 max-w-sm mx-auto leading-relaxed mb-6">
                {{ __('You\'re all caught up! Check back later for updates.') }}
            </p>
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-[#F8EBD5] dark:bg-yellow-900/30 border border-[#9B9EA4]/20 dark:border-yellow-700/30">
                <flux:icon name="check-circle" class="w-4 h-4 text-green-600 dark:text-green-400" />
                <span class="text-sm font-medium text-[#231F20] dark:text-white">
                    {{ __('All notifications read') }}
                </span>
            </div>
        </div>
        @endif

    </div>

    <style>
        /* Custom Scrollbar for better UX */
        ::-webkit-scrollbar {
            width: 5px;
        }

        ::-webkit-scrollbar-track {
            background: #F8EBD5;
        }

        ::-webkit-scrollbar-thumb {
            background: #FFF200;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #F4E000;
        }

        .dark ::-webkit-scrollbar-track {
            background: #374151;
        }

        .dark ::-webkit-scrollbar-thumb {
            background: #F59E0B;
        }

        .dark ::-webkit-scrollbar-thumb:hover {
            background: #D97706;
        }

        /* Smooth transitions for all interactive elements */
        * {
            transition: all 0.2s ease-in-out;
        }
    </style>
</div>