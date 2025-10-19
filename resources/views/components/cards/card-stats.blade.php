{{-- Card Stats Component --}}
@props([
    'stats' => [],
    'columns' => 2,
    'class' => '',
])

@php
    $gridClasses = match($columns) {
        1 => 'grid-cols-1',
        2 => 'grid-cols-2',
        3 => 'grid-cols-3',
        4 => 'grid-cols-4',
        default => 'grid-cols-2'
    };

    $containerClasses = 'grid ' . $gridClasses . ' gap-4 p-6 pt-0 ' . $class;
@endphp

@if(!empty($stats))
    <div class="{{ $containerClasses }}">
        @foreach($stats as $stat)
            @php
                $value = $stat['value'] ?? '';
                $label = $stat['label'] ?? '';
                $icon = $stat['icon'] ?? null;
                $change = $stat['change'] ?? null;
                $changeType = $stat['changeType'] ?? 'neutral'; // positive, negative, neutral
                $variant = $stat['variant'] ?? 'default';
            @endphp

            <div class="flex items-center space-x-3">
                @if($icon)
                    <div class="flex-shrink-0">
                        <div class="h-8 w-8 rounded-lg
                            @if($variant === 'primary')
                                bg-[#2563EB]/10
                            @elseif($variant === 'success')
                                bg-green-100 dark:bg-green-900/20
                            @elseif($variant === 'warning')
                                bg-yellow-100 dark:bg-yellow-900/20
                            @elseif($variant === 'danger')
                                bg-red-100 dark:bg-red-900/20
                            @else
                                bg-gray-100 dark:bg-zinc-800
                            @endif
                            flex items-center justify-center">
                            <flux:icon name="{{ $icon }}" class="h-4 w-4
                                @if($variant === 'primary')
                                    text-[#2563EB]
                                @elseif($variant === 'success')
                                    text-green-600 dark:text-green-400
                                @elseif($variant === 'warning')
                                    text-yellow-600 dark:text-yellow-400
                                @elseif($variant === 'danger')
                                    text-red-600 dark:text-red-400
                                @else
                                    text-[#9B9EA4] dark:text-zinc-400
                                @endif" />
                        </div>
                    </div>
                @endif

                <div class="flex-1 min-w-0">
                    <div class="text-2xl font-bold text-[#231F20] dark:text-white">
                        {{ $value }}
                    </div>
                    <div class="text-sm text-[#9B9EA4] dark:text-zinc-400">
                        {{ $label }}
                    </div>
                    @if($change)
                        <div class="flex items-center mt-1">
                            @if($changeType === 'positive')
                                <flux:icon name="arrow-up" class="h-3 w-3 text-green-500 mr-1" />
                                <span class="text-xs text-green-600 dark:text-green-400">
                                    {{ $change }}
                                </span>
                            @elseif($changeType === 'negative')
                                <flux:icon name="arrow-down" class="h-3 w-3 text-red-500 mr-1" />
                                <span class="text-xs text-red-600 dark:text-red-400">
                                    {{ $change }}
                                </span>
                            @else
                                <span class="text-xs text-[#9B9EA4] dark:text-zinc-400">
                                    {{ $change }}
                                </span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif