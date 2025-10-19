{{-- Card Footer Component --}}
@props([
    'actions' => [],
    'meta' => [],
    'class' => '',
])

@php
    $footerClasses = 'flex flex-col gap-4 items-center justify-between px-6 pt-4 pb-6 border-t border-[#E6E8EB] dark:border-zinc-700 ' . $class;
@endphp

@if(!empty($actions) || !empty($meta) || $slot->isNotEmpty())
    <div class="{{ $footerClasses }}">
        <div class="flex-1">
            @if(!empty($meta))
                <div class="flex items-center space-x-4">
                    @foreach($meta as $item)
                        <div class="flex items-center text-xs text-[#9B9EA4] dark:text-zinc-400">
                            @if(isset($item['icon']))
                                <flux:icon name="{{ $item['icon'] }}" class="h-3 w-3 mr-1" />
                            @endif
                            {{ $item['text'] }}
                        </div>
                    @endforeach
                </div>
            @else
                {{ $slot }}
            @endif
        </div>

        @if(!empty($actions))
            <div class="flex-shrink-0 w-full">
                <x-cards.card-actions :actions="$actions" justify="justify-between" class="" />
            </div>
        @endif
    </div>
@endif