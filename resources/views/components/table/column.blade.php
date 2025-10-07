{{-- Table Column Component --}}
@props([
    'sortable' => false,
    'sortField' => null,
    'sortDirection' => null,
    'currentSort' => null,
    'currentDirection' => 'asc',
    'width' => null,
    'align' => 'left',
    'class' => '',
    'sticky' => false,
])

@php
    $thClasses = 'px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider';

    // Alignment classes
    if ($align === 'center') {
        $thClasses .= ' text-center';
    } elseif ($align === 'right') {
        $thClasses .= ' text-right';
    }

    // Sticky positioning
    if ($sticky) {
        $thClasses .= ' sticky top-0 z-10 bg-gray-50 dark:bg-gray-800';
    }

    // Sortable styling
    if ($sortable) {
        $thClasses .= ' cursor-pointer select-none hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-150';
    }

    // Width styling
    if ($width) {
        $thClasses .= ' w-' . $width;
    }

    $thClasses .= ' ' . $class;

    // Sort indicator
    $sortIcon = null;
    $sortClasses = 'ml-1 h-4 w-4 inline-block transition-transform duration-200';

    if ($sortable && $currentSort === $sortField) {
        if ($currentDirection === 'asc') {
            $sortIcon = 'chevron-up';
            $sortClasses .= ' text-blue-600 dark:text-blue-400';
        } else {
            $sortIcon = 'chevron-down';
            $sortClasses .= ' text-blue-600 dark:text-blue-400';
        }
    } elseif ($sortable) {
        $sortIcon = 'chevron-up-down';
        $sortClasses .= ' text-gray-400 dark:text-gray-500 opacity-50';
    }
@endphp

<th
    class="{{ $thClasses }}"
    role="columnheader"
    @if($sortable)
        wire:click="sortBy('{{ $sortField }}')"
        aria-sort="{{ $currentSort === $sortField ? ($currentDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}"
    @endif
    scope="col"
>
    <div class="flex items-center {{ $align === 'right' ? 'justify-end' : ($align === 'center' ? 'justify-center' : 'justify-start') }}">
        <span>{{ $slot }}</span>
        @if($sortIcon)
            <flux:icon name="{{ $sortIcon }}" class="{{ $sortClasses }}" />
        @endif
    </div>
</th>