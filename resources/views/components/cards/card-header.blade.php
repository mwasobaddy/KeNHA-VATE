{{-- Card Header Component --}}
@props([
    'title' => null,
    'subtitle' => null,
    'avatar' => null,
    'badge' => null,
    'meta' => [],
    'actions' => [],
    'class' => '',
])

@php
    $headerClasses = 'flex items-start justify-between p-6 pb-4 ' . $class;
@endphp

<div class="{{ $headerClasses }}">
    <div class="flex items-start space-x-3 flex-1 min-w-0">
        @if($avatar)
            <div class="flex-shrink-0">
                @if(is_array($avatar))
                    <img
                        src="{{ $avatar['src'] ?? '' }}"
                        alt="{{ $avatar['alt'] ?? 'Avatar' }}"
                        class="h-10 w-10 rounded-full object-cover {{ $avatar['class'] ?? '' }}"
                    >
                @else
                    <div class="h-10 w-10 rounded-full bg-gray-200 dark:bg-zinc-700 flex items-center justify-center">
                        <flux:icon name="user" class="h-5 w-5 text-gray-500 dark:text-zinc-400" />
                    </div>
                @endif
            </div>
        @endif

        <div class="flex-1 min-w-0">
            @if($title)
                <div class="flex items-center space-x-2">
                    <h3 class="text-lg font-semibold text-[#231F20] dark:text-white truncate">
                        {{ $title }}
                    </h3>
                    @if($badge)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            @if($badge['variant'] === 'success')
                                bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                            @elseif($badge['variant'] === 'warning')
                                bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                            @elseif($badge['variant'] === 'danger')
                                bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                            @else
                                bg-gray-100 text-gray-800 dark:bg-zinc-800 dark:text-zinc-200
                            @endif">
                            {{ $badge['text'] }}
                        </span>
                    @endif
                </div>
            @endif

            @if($subtitle)
                <p class="text-sm text-[#9B9EA4] dark:text-zinc-400 mt-1">
                    {{ $subtitle }}
                </p>
            @endif

            @if(!empty($meta))
                <div class="flex items-center space-x-4 mt-2">
                    @foreach($meta as $item)
                        <div class="flex items-center text-xs text-[#9B9EA4] dark:text-zinc-400">
                            @if(isset($item['icon']))
                                <flux:icon name="{{ $item['icon'] }}" class="h-3 w-3 mr-1" />
                            @endif
                            {{ $item['text'] }}
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    @if(!empty($actions))
        <div class="flex-shrink-0 ml-4">
            <x-cards.card-actions :actions="$actions" justify="justify-end" />
        </div>
    @endif
</div>