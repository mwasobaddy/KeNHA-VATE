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
    // Base header classes (zinc palette for dark mode)
    $thClasses = 'px-6 py-3 text-left text-xs font-medium text-[#6B7280] dark:text-zinc-400 uppercase tracking-wider';

    // Alignment classes
    if ($align === 'center') {
        $thClasses .= ' text-center';
    } elseif ($align === 'right') {
        $thClasses .= ' text-right';
    }

    // Sticky positioning
    if ($sticky) {
        $thClasses .= ' sticky top-0 z-10 bg-white dark:bg-zinc-800';
    }

    // Sortable styling
    if ($sortable) {
        $thClasses .= ' cursor-pointer select-none hover:bg-gray-50 dark:hover:bg-zinc-900 transition-colors duration-150';
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
            $sortClasses .= ' text-[#2563EB] dark:text-[#60A5FA]';
        } else {
            $sortIcon = 'chevron-down';
            $sortClasses .= ' text-[#2563EB] dark:text-[#60A5FA]';
        }
    } elseif ($sortable) {
        $sortIcon = 'chevron-up-down';
        $sortClasses .= ' text-[#9CA3AF] dark:text-zinc-500 opacity-60';
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