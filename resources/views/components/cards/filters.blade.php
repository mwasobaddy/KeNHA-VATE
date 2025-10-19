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
        <div class="flex items-center justify-between gap-4 flex-wrap">
            {{-- Left: Search --}}
            <div class="flex items-center gap-3 flex-1">
                <flux:input
                    icon="magnifying-glass"
                    wire:model.live.debounce.300ms="search"
                    type="text"
                    :loading="false"
                    placeholder="{{ $searchPlaceholder }}"
                    class="min-w-64 text-sm bg-white dark:bg-zinc-800 text-[#231F20] dark:text-white focus:ring-2 focus:ring-[#FFF200] focus:border-[#FFF200] transition-all duration-200"
                />
            </div>

            {{-- Right: Controls (Filter toggle, bulk actions, per-page) --}}
            <div class="flex items-center gap-3 flex-1 justify-end">
                {{-- Filter Toggle Button --}}
                @if(!empty($filters))
                <flux:button
                    icon="adjustments-horizontal"
                    type="button"
                    @click="open = !open"
                    variant="primary"
                    color="gray"
                    aria-expanded="false"
                >
                    <span class="hidden sm:inline">Filters</span>
                </flux:button>
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
                    <span class="text-[#9B9EA4] dark:text-zinc-400 hidden sm:inline">Show</span>
                    <flux:select
                        wire:model.live="perPage"
                        class="border border-[#9B9EA4]/20 dark:border-zinc-700 rounded-md px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-[#231F20] dark:text-white focus:ring-2 focus:ring-[#FFF200] focus:border-[#FFF200] transition-all duration-200"
                    >
                        @foreach($perPageOptions as $option)
                            <flux:select.option value="{{ $option }}">{{ $option }}</flux:select.option>
                        @endforeach
                    </flux:select>
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
                @else
                    <div class="flex flex-wrap gap-2 w-full">
                        {{ $slot }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>