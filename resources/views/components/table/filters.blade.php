{{-- Table Filters Component --}}
@props([
    'search' => '',
    'searchPlaceholder' => 'Search...',
    'filters' => [],
    'perPage' => 10,
    'perPageOptions' => [10, 25, 50, 100],
    'showBulkActions' => false,
    'selectedCount' => 0,
    'class' => '',
])

<div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between mb-6 {{ $class }}">
    {{-- Left Side: Search and Filters --}}
    <div class="flex flex-col sm:flex-row gap-3 flex-1">
        {{-- Search Input --}}
        <div class="relative">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="text"
                placeholder="{{ $searchPlaceholder }}"
                class="pl-10"
            />
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <flux:icon name="magnifying-glass" class="h-5 w-5 text-gray-400" />
            </div>
        </div>

        {{-- Additional Filters --}}
        @if(!empty($filters))
        <div class="flex gap-2">
            {{ $filters }}
        </div>
        @endif
    </div>

    {{-- Right Side: Per Page and Bulk Actions --}}
    <div class="flex items-center gap-3">
        {{-- Bulk Actions --}}
        @if($showBulkActions && $selectedCount > 0)
        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
            <span>{{ $selectedCount }} selected</span>
            <div class="flex gap-1">
                {{ $bulkActions ?? '' }}
            </div>
        </div>
        @endif

        {{-- Per Page Selector --}}
        <div class="flex items-center gap-2 text-sm">
            <span class="text-gray-600 dark:text-gray-400">Show</span>
            <select
                wire:model.live="perPage"
                class="border border-gray-300 dark:border-gray-600 rounded-md px-2 py-1 text-sm bg-white dark:bg-gray-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
                @foreach($perPageOptions as $option)
                    <option value="{{ $option }}">{{ $option }}</option>
                @endforeach
            </select>
            <span class="text-gray-600 dark:text-gray-400">per page</span>
        </div>
    </div>
</div>