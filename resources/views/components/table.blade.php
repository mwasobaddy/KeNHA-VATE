{{-- Table Component - Modern, Accessible, Responsive --}}
@props([
    'striped' => false,
    'hover' => true,
    'bordered' => false,
    'compact' => false,
    'responsive' => true,
    'loading' => false,
    'empty' => false,
    'emptyIcon' => 'folder-open',
    'emptyTitle' => 'No data found',
    'emptyDescription' => 'There are no items to display at the moment.',
    'class' => '',
])

@php
    $tableClasses = 'min-w-full divide-y divide-zinc-200 dark:divide-zinc-700';
    $containerClasses = 'bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden';

    if ($striped) {
        $tableClasses .= ' divide-y-0';
    }

    if ($compact) {
        $tableClasses .= ' text-sm';
    }

    if ($bordered) {
        $containerClasses .= ' border-2';
    }

    if ($responsive) {
        $containerClasses = 'overflow-x-auto ' . $containerClasses;
    }

    $containerClasses .= ' ' . $class;
@endphp

@if($responsive)
<div class="{{ $containerClasses }}">
@endif

    <table class="{{ $tableClasses }}" role="table">
        {{-- Table Header --}}
        <thead class="bg-zinc-50 dark:bg-zinc-800" role="rowgroup">
            {{ $head ?? '' }}
        </thead>

        {{-- Table Body --}}
        <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700" role="rowgroup">
            @if($loading)
                {{-- Loading State --}}
                <tr role="row">
                    <td colspan="100%" class="px-6 py-12 text-center" role="cell">
                        <div class="flex items-center justify-center">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                            <span class="ml-3 text-zinc-600 dark:text-zinc-400">Loading...</span>
                        </div>
                    </td>
                </tr>
            @elseif($empty)
                {{-- Empty State --}}
                <tr role="row">
                    <td colspan="100%" class="px-6 py-12 text-center" role="cell">
                        <div class="flex flex-col items-center justify-center space-y-3">
                            <flux:icon name="{{ $emptyIcon }}" class="h-12 w-12 text-zinc-400 dark:text-zinc-500" />
                            <div>
                                <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100">{{ $emptyTitle }}</h3>
                                <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">{{ $emptyDescription }}</p>
                            </div>
                            {{ $emptyActions ?? '' }}
                        </div>
                    </td>
                </tr>
            @else
                {{ $body ?? '' }}
            @endif
        </tbody>

        {{-- Table Footer (Optional) --}}
        @if(isset($foot))
        <tfoot class="bg-zinc-50 dark:bg-zinc-800" role="rowgroup">
            {{ $foot }}
        </tfoot>
        @endif
    </table>

@if($responsive)
</div>
@endif