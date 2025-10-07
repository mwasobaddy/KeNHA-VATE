{{-- Table Pagination Component --}}
@props([
    'paginator' => null,
    'showInfo' => true,
    'class' => '',
])

@php
    if (!$paginator) {
        return;
    }

    $currentPage = $paginator->currentPage();
    $lastPage = $paginator->lastPage();
    $total = $paginator->total();
    $perPage = $paginator->perPage();
    $from = $paginator->firstItem() ?? 0;
    $to = $paginator->lastItem() ?? 0;
@endphp

@if($paginator->hasPages())
<div class="flex flex-col sm:flex-row items-center justify-between gap-4 px-6 py-4 bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 {{ $class }}">
    {{-- Results Info --}}
    @if($showInfo)
    <div class="text-sm text-gray-700 dark:text-gray-300">
        Showing <span class="font-medium">{{ $from }}</span> to <span class="font-medium">{{ $to }}</span> of <span class="font-medium">{{ $total }}</span> results
    </div>
    @endif

    {{-- Pagination Controls --}}
    <div class="flex items-center space-x-1">
        {{-- Previous Button --}}
        @if($paginator->onFirstPage())
            <span class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-400 dark:text-gray-500 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-l-md cursor-not-allowed">
                <flux:icon name="chevron-left" class="h-5 w-5" />
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" wire:navigate class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-l-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                <flux:icon name="chevron-left" class="h-5 w-5" />
            </a>
        @endif

        {{-- Page Numbers --}}
        @foreach($paginator->getUrlRange(max(5, $currentPage - 2), min($lastPage, $currentPage + 2)) as $page)
            @if($page == $currentPage)
                <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-blue-600 cursor-default">
                    {{ $page }}
                </span>
            @else
                <a href="{{ $paginator->url($page) }}" wire:navigate class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                    {{ $page }}
                </a>
            @endif
        @endforeach

        {{-- Next Button --}}
        @if($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" wire:navigate class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-r-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                <flux:icon name="chevron-right" class="h-5 w-5" />
            </a>
        @else
            <span class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-400 dark:text-gray-500 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-r-md cursor-not-allowed">
                <flux:icon name="chevron-right" class="h-5 w-5" />
            </span>
        @endif
    </div>
</div>
@endif