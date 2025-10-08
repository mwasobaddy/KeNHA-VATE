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

{{-- Filters Card Container with toggle --}}
<div x-data="{ open: false }" class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm overflow-hidden transition-all duration-200 hover:shadow-md {{ $class }}">
    <div class="p-4 sm:p-6">
        <div class="flex items-center justify-between gap-4">
            {{-- Left: Search --}}
            <div class="flex items-center gap-3 flex-1">
                <div class="relative w-full sm:w-80">
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        type="text"
                        placeholder="{{ $searchPlaceholder }}"
                        class="w-full pl-10 border border-[#9B9EA4]/20 dark:border-zinc-700 rounded-md px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-[#231F20] dark:text-white focus:ring-2 focus:ring-[#FFF200] focus:border-[#FFF200] transition-all duration-200"
                    />
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <flux:icon name="magnifying-glass" class="h-5 w-5 text-[#9B9EA4] dark:text-zinc-400" />
                    </div>
                </div>

                {{-- Optional compact filters indicator on small screens --}}
                <div class="hidden sm:flex items-center text-sm text-[#9B9EA4] dark:text-zinc-400">
                    @if(!empty($filters))
                        <span class="ml-1">Filters available</span>
                    @endif
                </div>
            </div>

            {{-- Right: Controls (Filter toggle, bulk actions, per-page) --}}
            <div class="flex items-center gap-3">
                {{-- Filter Toggle Button --}}
                @if(!empty($filters))
                <button
                    type="button"
                    @click="open = !open"
                    class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-md bg-white dark:bg-zinc-900 border border-[#E6E8EB] dark:border-zinc-700 text-gray-700 dark:text-white hover:bg-gray-50 dark:hover:bg-zinc-800 transition-colors duration-150"
                    aria-expanded="false"
                >
                    <flux:icon name="adjustments-horizontal" class="h-4 w-4 text-gray-600 dark:text-zinc-300" />
                    <span class="hidden sm:inline">Filters</span>
                </button>
                @endif

                {{-- Bulk Actions summary (keeps backward compatibility) --}}
                @if($showBulkActions && $selectedCount > 0)
                <div class="flex items-center gap-2 text-sm text-[#9B9EA4] dark:text-zinc-400">
                    <span class="font-medium">{{ $selectedCount }} selected</span>
                    <div class="flex gap-1">
                        {{ $bulkActions ?? '' }}
                    </div>
                </div>
                @endif

                {{-- Per Page Selector --}}
                <div class="flex items-center gap-2 text-sm">
                    <span class="text-[#9B9EA4] dark:text-zinc-400">Show</span>
                    <select
                        wire:model.live="perPage"
                        class="border border-[#9B9EA4]/20 dark:border-zinc-700 rounded-md px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-[#231F20] dark:text-white focus:ring-2 focus:ring-[#FFF200] focus:border-[#FFF200] transition-all duration-200"
                    >
                        @foreach($perPageOptions as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Collapsible Filters Panel --}}
        <div x-show="open" x-transition class="mt-4">
            <div class="flex flex-col sm:flex-row gap-3">
                @if(!empty($filters))
                    <div class="flex flex-wrap gap-2 w-full">
                        {{ $filters }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>