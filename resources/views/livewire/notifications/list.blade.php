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
<div class="space-y-6" wire:key="notifications-container-{{ auth()->id() }}">
    <!-- Modern Header with Glassmorphism -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-white/80 via-white/60 to-white/40 dark:from-zinc-900/80 dark:via-zinc-800/60 dark:to-zinc-900/40 backdrop-blur-xl border border-white/20 dark:border-zinc-700/50 shadow-xl">
        <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 via-purple-500/5 to-pink-500/5"></div>
        <div class="relative p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-xl bg-gradient-to-br from-blue-500 to-purple-600 shadow-lg">
                        <flux:icon name="bell" class="w-6 h-6 text-white" />
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-zinc-900 dark:text-zinc-100">
                            {{ __('Notifications') }}
                        </h3>
                        @if($unreadCount > 0)
                            <div class="flex items-center gap-2 mt-1">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-md animate-pulse">
                                    <flux:icon name="star" class="w-3 h-3 mr-1" />
                                    {{ $unreadCount }} {{ __('unread') }}
                                </span>
                            </div>
                        @else
                            <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
                                {{ __('All caught up!') }}
                            </p>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    @if($unreadCount > 0)
                        <flux:button
                            variant="outline"
                            size="sm"
                            wire:click="markAllAsRead"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50 cursor-not-allowed"
                            :disabled="$loading"
                            class="bg-white/50 hover:bg-white/70 dark:bg-zinc-800/50 dark:hover:bg-zinc-800/70 border-white/30 dark:border-zinc-600/30 backdrop-blur-sm transition-all duration-200 hover:shadow-md"
                        >
                            <flux:icon name="check-badge" class="w-4 h-4 mr-2" />
                            <span wire:loading.remove>{{ __('Mark all read') }}</span>
                            <span wire:loading class="flex items-center gap-2">
                                <div class="w-4 h-4 border-2 border-current border-t-transparent rounded-full animate-spin"></div>
                                Marking...
                            </span>
                        </flux:button>
                    @endif

                    <flux:button
                        variant="ghost"
                        size="sm"
                        wire:click="toggleShowAll"
                        class="text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100 transition-colors duration-200"
                    >
                        <flux:icon name="{{ $showAll ? 'chevron-up' : 'chevron-down' }}" class="w-4 h-4 mr-2" />
                        {{ $showAll ? __('Show less') : __('Show all') }}
                    </flux:button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modern Notifications List -->
    <div class="space-y-4">
        @foreach($notifications as $notification)
            <div class="group relative overflow-hidden rounded-xl bg-gradient-to-br from-white/70 via-white/50 to-white/30 dark:from-zinc-800/70 dark:via-zinc-800/50 dark:to-zinc-800/30 backdrop-blur-lg border border-white/20 dark:border-zinc-700/30 shadow-lg hover:shadow-xl transition-all duration-300 {{ $notification->isRead() ? 'opacity-80 hover:opacity-90' : 'ring-2 ring-blue-500/20 hover:ring-blue-500/40' }}"
                 wire:key="notification-{{ $notification->id }}"
                 x-data="{ expanded: false }"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100">

                <!-- Animated background gradient -->
                <div class="absolute inset-0 bg-gradient-to-br {{ $notification->type === 'success' ? 'from-green-500/5 via-emerald-500/5 to-teal-500/5' : ($notification->type === 'error' ? 'from-red-500/5 via-rose-500/5 to-pink-500/5' : ($notification->type === 'warning' ? 'from-yellow-500/5 via-amber-500/5 to-orange-500/5' : 'from-blue-500/5 via-indigo-500/5 to-purple-500/5')) }}"></div>

                <!-- Unread indicator -->
                @if(!$notification->isRead())
                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-blue-500 to-purple-600"></div>
                @endif

                <div class="relative p-5">
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3 mb-3">
                                <!-- Notification Icon -->
                                <div class="flex-shrink-0 p-2 rounded-lg {{ $notification->type === 'success' ? 'bg-green-100 dark:bg-green-900/30' : ($notification->type === 'error' ? 'bg-red-100 dark:bg-red-900/30' : ($notification->type === 'warning' ? 'bg-yellow-100 dark:bg-yellow-900/30' : 'bg-blue-100 dark:bg-blue-900/30')) }}">
                                    @if($notification->type === 'success')
                                        <flux:icon name="check-circle" class="w-5 h-5 text-green-600 dark:text-green-400" />
                                    @elseif($notification->type === 'error')
                                        <flux:icon name="x-circle" class="w-5 h-5 text-red-600 dark:text-red-400" />
                                    @elseif($notification->type === 'warning')
                                        <flux:icon name="exclamation-triangle" class="w-5 h-5 text-yellow-600 dark:text-yellow-400" />
                                    @else
                                        <flux:icon name="information-circle" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                    @endif
                                </div>

                                <!-- Title and Status -->
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-semibold text-zinc-900 dark:text-zinc-100 text-base leading-tight">
                                        {{ $notification->title }}
                                    </h4>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400 font-medium">
                                            {{ $notification->created_at->diffForHumans() }}
                                        </span>
                                        @if(!$notification->isRead())
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                New
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Message -->
                            <div class="ml-11">
                                <p class="text-sm text-zinc-700 dark:text-zinc-300 leading-relaxed">
                                    {{ $notification->message }}
                                </p>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-2 ml-4 flex-shrink-0">
                            @if($notification->action_url)
                                <flux:link
                                    :href="$notification->action_url"
                                    class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/20 dark:hover:bg-blue-900/30 rounded-lg transition-colors duration-200"
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
                                    class="p-2 text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition-all duration-200"
                                    title="Mark as read"
                                >
                                    <flux:icon name="check" class="w-4 h-4" />
                                </flux:button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if(!$showAll && $notifications->count() >= 10)
        <div class="text-center py-6">
            <flux:button
                variant="outline"
                wire:click="toggleShowAll"
                class="bg-white/50 hover:bg-white/70 dark:bg-zinc-800/50 dark:hover:bg-zinc-800/70 border-white/30 dark:border-zinc-600/30 backdrop-blur-sm transition-all duration-200 hover:shadow-md"
            >
                <flux:icon name="chevron-down" class="w-4 h-4 mr-2" />
                {{ __('Load more notifications') }}
            </flux:button>
        </div>
    @endif
</div>
@else
<!-- Modern Empty State -->
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-white/80 via-white/60 to-white/40 dark:from-zinc-900/80 dark:via-zinc-800/60 dark:to-zinc-900/40 backdrop-blur-xl border border-white/20 dark:border-zinc-700/50 shadow-xl" wire:key="no-notifications-{{ auth()->id() }}">
    <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 via-purple-500/5 to-pink-500/5"></div>
    <div class="relative text-center py-12 px-6">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gradient-to-br from-blue-100 to-purple-100 dark:from-blue-900/30 dark:to-purple-900/30 mb-6 shadow-lg">
            <flux:icon name="bell" class="w-10 h-10 text-blue-600 dark:text-blue-400" />
        </div>
        <h3 class="text-xl font-bold text-zinc-900 dark:text-zinc-100 mb-3">
            {{ __('No notifications') }}
        </h3>
        <p class="text-base text-zinc-600 dark:text-zinc-400 max-w-sm mx-auto leading-relaxed">
            {{ __('You\'re all caught up! Check back later for updates.') }}
        </p>
        <div class="mt-6">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border border-green-200/50 dark:border-green-700/30">
                <flux:icon name="check-circle" class="w-4 h-4 text-green-600 dark:text-green-400" />
                <span class="text-sm font-medium text-green-700 dark:text-green-300">
                    All notifications read
                </span>
            </div>
        </div>
    </div>
</div>
@endif