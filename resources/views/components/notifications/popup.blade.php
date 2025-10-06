{{-- Popup Notification Component --}}
@props([
    'type' => 'info',
    'title' => '',
    'message' => '',
    'duration' => 5000,
    'id' => null,
    'index' => 0
])

@php
    $id = $id ?? 'notification-' . uniqid();
    $icon = match($type) {
        'success' => 'check-circle',
        'error' => 'exclamation-circle',
        'warning' => 'exclamation-triangle',
        'info' => 'information-circle',
        default => 'information-circle'
    };

    $bgColor = match($type) {
        'success' => 'bg-green-50 border-green-200 dark:bg-green-900/20 dark:border-green-800',
        'error' => 'bg-red-50 border-red-200 dark:bg-red-900/20 dark:border-red-800',
        'warning' => 'bg-yellow-50 border-yellow-200 dark:bg-yellow-900/20 dark:border-yellow-800',
        'info' => 'bg-blue-50 border-blue-200 dark:bg-blue-900/20 dark:border-blue-800',
        default => 'bg-blue-50 border-blue-200 dark:bg-blue-900/20 dark:border-blue-800'
    };

    $iconColor = match($type) {
        'success' => 'text-green-600 dark:text-green-400',
        'error' => 'text-red-600 dark:text-red-400',
        'warning' => 'text-yellow-600 dark:text-yellow-400',
        'info' => 'text-blue-600 dark:text-blue-400',
        default => 'text-blue-600 dark:text-blue-400'
    };

    $titleColor = match($type) {
        'success' => 'text-green-800 dark:text-green-200',
        'error' => 'text-red-800 dark:text-red-200',
        'warning' => 'text-yellow-800 dark:text-yellow-200',
        'info' => 'text-blue-800 dark:text-blue-200',
        default => 'text-blue-800 dark:text-blue-200'
    };
@endphp

<div
    id="{{ $id }}"
    x-data="{
        show: true,
        init() {
            setTimeout(() => {
                this.show = false;
                setTimeout(() => {
                    this.$el.remove();
                }, 300);
            }, {{ $duration }});
        }
    }"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform translate-y-2"
    x-transition:enter-end="opacity-100 transform translate-y-0"
    x-transition:leave="transition ease-in duration-300"
    x-transition:leave-start="opacity-100 transform translate-y-0"
    x-transition:leave-end="opacity-0 transform -translate-y-2"
    class="fixed top-4 right-4 z-50 max-w-sm w-full {{ $bgColor }} border rounded-lg shadow-lg p-4 space-y-4"
    role="alert"
    style="margin-top: calc(4rem * var(--popup-index, 0));"
    x-init="$el.style.setProperty('--popup-index', $el.dataset.index || 0)"
    data-index="{{ $index }}"
>
    <div class="flex items-start">
        <div class="flex-shrink-0">
            <flux:icon name="{{ $icon }}" class="h-5 w-5 {{ $iconColor }}" />
        </div>
        <div class="ml-3 w-0 flex-1">
            @if($title)
                <p class="text-sm font-medium {{ $titleColor }}">
                    {{ $title }}
                </p>
            @endif
            <p class="text-sm {{ $title ? 'mt-1' : '' }} text-gray-700 dark:text-gray-300">
                {{ $message }}
            </p>
        </div>
        <div class="ml-4 flex-shrink-0 flex">
            <button
                @click="show = false; setTimeout(() => $el.remove(), 300)"
                class="inline-flex text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
                <span class="sr-only">Close</span>
                <flux:icon name="x-mark" class="h-5 w-5" />
            </button>
        </div>
    </div>
</div>