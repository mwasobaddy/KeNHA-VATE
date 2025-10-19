{{-- Card Grid Container Component --}}
@props([
    'columns' => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
    'gap' => 'gap-6',
    'class' => '',
    'loading' => false,
    'empty' => false,
    'emptyTitle' => 'No items found',
    'emptyDescription' => 'Try adjusting your search or filters to find what you\'re looking for.',
])

@php
    $gridClasses = 'grid ' . $columns . ' ' . $gap . ' ' . $class;
@endphp

@if($loading)
    {{-- Loading State --}}
    <div class="{{ $gridClasses }}">
        @for($i = 0; $i < 6; $i++)
            <div class="bg-white dark:bg-zinc-800 border border-[#9B9EA4]/20 dark:border-zinc-700 rounded-2xl p-6 shadow-lg animate-pulse">
                <div class="h-4 bg-gray-200 dark:bg-zinc-700 rounded w-3/4 mb-4"></div>
                <div class="h-3 bg-gray-200 dark:bg-zinc-700 rounded w-1/2 mb-3"></div>
                <div class="h-3 bg-gray-200 dark:bg-zinc-700 rounded w-2/3 mb-4"></div>
                <div class="flex justify-between items-center">
                    <div class="h-8 bg-gray-200 dark:bg-zinc-700 rounded w-20"></div>
                    <div class="h-6 bg-gray-200 dark:bg-zinc-700 rounded w-16"></div>
                </div>
            </div>
        @endfor
    </div>
@elseif($empty)
    {{-- Empty State --}}
    <x-cards.empty-state
        :title="$emptyTitle"
        :description="$emptyDescription"
    />
@else
    {{-- Card Grid --}}
    <div class="{{ $gridClasses }}">
        {{ $slot }}
    </div>
@endif