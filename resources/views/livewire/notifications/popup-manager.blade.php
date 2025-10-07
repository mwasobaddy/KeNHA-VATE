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
            session()->forget('popup_notifications');
        }

        // Check for immediate popup notification (from redirects)
        $immediateNotification = session('immediate_popup_notification', null);
        if ($immediateNotification) {
            $this->notifications[] = [
                'id' => uniqid('popup-'),
                'type' => $immediateNotification['type'] ?? 'info',
                'title' => $immediateNotification['title'] ?? '',
                'message' => $immediateNotification['message'] ?? '',
                'duration' => $immediateNotification['duration'] ?? 5000,
            ];
            session()->forget('immediate_popup_notification');
        }
    }

    public function updated(): void
    {
        $immediateNotification = session('immediate_popup_notification', null);
        if ($immediateNotification) {
            $this->notifications[] = [
                'id' => uniqid('popup-'),
                'type' => $immediateNotification['type'] ?? 'info',
                'title' => $immediateNotification['title'] ?? '',
                'message' => $immediateNotification['message'] ?? '',
                'duration' => $immediateNotification['duration'] ?? 5000,
            ];
            session()->forget('immediate_popup_notification');
        }
    }

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

        $this->dispatch('notification-added', id: end($this->notifications)['id']);
    }

    public function showSuccess(string $title, string $message, int $duration = 5000): void
    {
        $this->showPopup('success', $title, $message, $duration);
    }

    public function showError(string $title, string $message, int $duration = 5000): void
    {
        $this->showPopup('error', $title, $message, $duration);
    }

    public function showWarning(string $title, string $message, int $duration = 5000): void
    {
        $this->showPopup('warning', $title, $message, $duration);
    }

    public function showInfo(string $title, string $message, int $duration = 5000): void
    {
        $this->showPopup('info', $title, $message, $duration);
    }

    public function removeNotification(string $id): void
    {
        $this->notifications = array_filter(
            $this->notifications,
            fn($notification) => $notification['id'] !== $id
        );
    }
}; ?>

{{-- 
    Modern Toast Notification System
    - Improved stacking with dynamic gap calculation
    - Enhanced visual design with glassmorphism effects
    - Smooth micro-interactions and animations
    - Better accessibility with ARIA labels
    - Fully responsive design
--}}

<div class="fixed inset-0 z-50 pointer-events-none">
    {{-- Toast Container with better positioning for mobile/desktop --}}
    <div class="sticky top-4 justify-self-end right-4 sm:left-auto sm:w-96 space-y-3 pointer-events-none">
        @foreach($notifications as $index => $notification)
            @php
                $id = $notification['id'];
                $type = $notification['type'];
                $title = $notification['title'];
                $message = $notification['message'];
                $duration = $notification['duration'];

                // Modern icon mapping with Heroicons
                $icon = match($type) {
                    'success' => 'check-circle',
                    'error' => 'x-circle',
                    'warning' => 'exclamation-triangle',
                    'info' => 'information-circle',
                    default => 'information-circle'
                };

                // Refined color scheme with subtle gradients and better dark mode
                $bgColor = match($type) {
                    'success' => 'bg-gradient-to-br from-emerald-50 to-green-50 border-emerald-200/50 dark:from-emerald-950/90 dark:to-green-950/90 dark:border-emerald-700/30',
                    'error' => 'bg-gradient-to-br from-rose-50 to-red-50 border-rose-200/50 dark:from-rose-950/90 dark:to-red-950/90 dark:border-rose-700/30',
                    'warning' => 'bg-gradient-to-br from-amber-50 to-yellow-50 border-amber-200/50 dark:from-amber-950/90 dark:to-yellow-950/90 dark:border-amber-700/30',
                    'info' => 'bg-gradient-to-br from-sky-50 to-blue-50 border-sky-200/50 dark:from-sky-950/90 dark:to-blue-950/90 dark:border-sky-700/30',
                    default => 'bg-gradient-to-br from-slate-50 to-gray-50 border-slate-200/50 dark:from-slate-950/90 dark:to-gray-950/90 dark:border-slate-700/30'
                };

                $iconColor = match($type) {
                    'success' => 'text-emerald-600 dark:text-emerald-400',
                    'error' => 'text-rose-600 dark:text-rose-400',
                    'warning' => 'text-amber-600 dark:text-amber-400',
                    'info' => 'text-sky-600 dark:text-sky-400',
                    default => 'text-slate-600 dark:text-slate-400'
                };

                $titleColor = match($type) {
                    'success' => 'text-emerald-900 dark:text-emerald-100',
                    'error' => 'text-rose-900 dark:text-rose-100',
                    'warning' => 'text-amber-900 dark:text-amber-100',
                    'info' => 'text-sky-900 dark:text-sky-100',
                    default => 'text-slate-900 dark:text-slate-100'
                };

                // Progress bar color
                $progressColor = match($type) {
                    'success' => 'bg-emerald-500 dark:bg-emerald-400',
                    'error' => 'bg-rose-500 dark:bg-rose-400',
                    'warning' => 'bg-amber-500 dark:bg-amber-400',
                    'info' => 'bg-sky-500 dark:bg-sky-400',
                    default => 'bg-slate-500 dark:bg-slate-400'
                };

                // Accessibility label
                $ariaLabel = match($type) {
                    'success' => 'Success notification',
                    'error' => 'Error notification',
                    'warning' => 'Warning notification',
                    'info' => 'Information notification',
                    default => 'Notification'
                };
            @endphp

            {{-- Individual Toast with Modern Design --}}
            <div
                id="{{ $id }}"
                x-data="{
                    show: false,
                    progress: 100,
                    interval: null,
                    init() {
                        // Delay initial show for stagger effect
                        setTimeout(() => {
                            this.show = true;
                        }, {{ $index * 100 }});
                        
                        // Progress bar animation
                        const duration = {{ $duration }};
                        const steps = 60; // 60fps
                        const decrement = 100 / (duration / (1000 / steps));
                        
                        this.interval = setInterval(() => {
                            this.progress -= decrement;
                            if (this.progress <= 0) {
                                clearInterval(this.interval);
                                this.close();
                            }
                        }, 1000 / steps);
                    },
                    close() {
                        clearInterval(this.interval);
                        this.show = false;
                        setTimeout(() => {
                            $wire.removeNotification('{{ $id }}');
                        }, 400);
                    },
                    pause() {
                        clearInterval(this.interval);
                    },
                    resume() {
                        const duration = {{ $duration }};
                        const steps = 60;
                        const decrement = 100 / (duration / (1000 / steps));
                        
                        this.interval = setInterval(() => {
                            this.progress -= decrement;
                            if (this.progress <= 0) {
                                clearInterval(this.interval);
                                this.close();
                            }
                        }, 1000 / steps);
                    }
                }"
                x-show="show"
                @mouseenter="pause()"
                @mouseleave="resume()"
                x-transition:enter="transition ease-out duration-400 transform"
                x-transition:enter-start="opacity-0 translate-x-8 scale-95"
                x-transition:enter-end="opacity-100 translate-x-0 scale-100"
                x-transition:leave="transition ease-in duration-300 transform"
                x-transition:leave-start="opacity-100 translate-x-0 scale-100"
                x-transition:leave-end="opacity-0 translate-x-8 scale-95"
                class="pointer-events-auto w-full {{ $bgColor }} border backdrop-blur-xl rounded-xl shadow-xl hover:shadow-2xl transition-all duration-300 overflow-hidden group"
                role="alert"
                aria-live="polite"
                aria-atomic="true"
                aria-label="{{ $ariaLabel }}"
            >
                {{-- Main Content --}}
                <div class="p-4">
                    <div class="flex items-start gap-3">
                        {{-- Icon with animated ring effect --}}
                        <div class="flex-shrink-0 relative">
                            <div class="absolute inset-0 {{ $iconColor }} opacity-20 blur-lg rounded-full group-hover:opacity-30 transition-opacity duration-300"></div>
                            <div class="relative">
                                <flux:icon 
                                    name="{{ $icon }}" 
                                    class="h-6 w-6 {{ $iconColor }} group-hover:scale-110 transition-transform duration-300" 
                                />
                            </div>
                        </div>

                        {{-- Text Content --}}
                        <div class="flex-1 min-w-0 pt-0.5">
                            @if($title)
                                <p class="text-sm font-semibold {{ $titleColor }} tracking-tight leading-snug">
                                    {{ $title }}
                                </p>
                            @endif
                            <p class="text-sm {{ $title ? 'mt-1' : '' }} text-gray-700 dark:text-gray-300 leading-relaxed">
                                {{ $message }}
                            </p>
                        </div>

                        {{-- Close Button with hover effect --}}
                        <button
                            @click="close()"
                            type="button"
                            class="flex-shrink-0 inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 hover:bg-white/50 dark:hover:bg-black/20 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-transparent focus:ring-gray-400 transition-all duration-200 group/btn"
                            aria-label="Close notification"
                        >
                            <flux:icon 
                                name="x-mark" 
                                class="h-5 w-5 group-hover/btn:rotate-90 transition-transform duration-300" 
                            />
                        </button>
                    </div>
                </div>

                {{-- Animated Progress Bar --}}
                <div class="h-1 bg-black/5 dark:bg-white/5 overflow-hidden">
                    <div 
                        class="{{ $progressColor }} h-full transition-all duration-100 ease-linear rounded-r-full"
                        :style="`width: ${progress}%`"
                    ></div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Optional: Add custom styles for enhanced effects --}}
    <style>
        /* Smooth scrollbar for long notifications */
        [x-cloak] { 
            display: none !important; 
        }
        
        /* Enhanced backdrop blur for supported browsers */
        @supports (backdrop-filter: blur(12px)) {
            .backdrop-blur-xl {
                backdrop-filter: blur(12px);
            }
        }
    </style>
</div>