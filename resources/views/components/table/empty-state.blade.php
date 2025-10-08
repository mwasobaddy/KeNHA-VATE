{{-- Table Empty State Component --}}
@props([
    'icon' => 'folder-open',
    'title' => 'No data found',
    'description' => 'There are no items to display at the moment.',
    'action' => null,
    'actionText' => null,
    'actionUrl' => null,
    'actionWireClick' => null,
    'class' => '',
])

<div class="flex flex-col items-center justify-center py-12 px-6 text-center {{ $class }}">
    {{-- Icon --}}
    <div class="flex-shrink-0">
        <flux:icon name="{{ $icon }}" class="h-16 w-16 text-[#9CA3AF] dark:text-zinc-500" />
    </div>

    {{-- Content --}}
    <div class="mt-4">
    <h3 class="text-lg font-medium text-[#231F20] dark:text-white">{{ $title }}</h3>
    <p class="mt-2 text-sm text-[#6B7280] dark:text-zinc-400">{{ $description }}</p>
    </div>

    {{-- Action Button --}}
    @if($action || $actionText)
    <div class="mt-6">
        @if($actionUrl)
            <a
                href="{{ $actionUrl }}"
                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-[#2563EB] hover:bg-[#1D4ED8] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#2563EB] transition-colors duration-200"
            >
                {{ $actionText ?? $action }}
            </a>
        @elseif($actionWireClick)
            <button
                wire:click="{{ $actionWireClick }}"
                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-[#2563EB] hover:bg-[#1D4ED8] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#2563EB] transition-colors duration-200"
            >
                {{ $actionText ?? $action }}
            </button>
        @else
            <button
                type="button"
                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-[#2563EB] hover:bg-[#1D4ED8] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#2563EB] transition-colors duration-200"
            >
                {{ $actionText ?? $action }}
            </button>
        @endif
    </div>
    @endif

    {{-- Custom Content Slot --}}
    {{ $slot }}
</div>