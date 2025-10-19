{{-- Card Component --}}
@props([
    'hover' => true,
    'selected' => false,
    'clickable' => false,
    'url' => null,
    'wireClick' => null,
    'class' => '',
    'padding' => 'p-6',
    'shadow' => 'shadow-lg',
    'border' => 'border-[#9B9EA4]/20 dark:border-zinc-700',
    'rounded' => 'rounded-2xl',
])

@php
    $cardClasses = 'bg-white dark:bg-zinc-800 border transition-all duration-200';

    if ($hover) {
        $cardClasses .= ' hover:shadow-xl hover:-translate-y-1 hover:border-[#FFF200]/50 dark:hover:border-yellow-400/50';
    }

    if ($selected) {
        $cardClasses .= ' ring-2 ring-[#2563EB] border-[#2563EB] bg-blue-50/50 dark:bg-blue-900/20';
    }

    if ($clickable || $url || $wireClick) {
        $cardClasses .= ' cursor-pointer';
    }

    $cardClasses .= ' ' . $padding . ' ' . $shadow . ' ' . $border . ' ' . $rounded . ' ' . $class;
@endphp

<div
    class="{{ $cardClasses }}"
    role="article"
    @if($url)
        onclick="window.location.href='{{ $url }}'"
    @elseif($wireClick)
        wire:click="{{ $wireClick }}"
    @elseif($clickable)
        onclick="{{ $attributes->get('onclick') }}"
    @endif
>
    {{ $slot }}
</div>