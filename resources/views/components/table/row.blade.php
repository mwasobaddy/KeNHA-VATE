{{-- Table Row Component --}}
@props([
    'hover' => true,
    'striped' => false,
    'selected' => false,
    'clickable' => false,
    'url' => null,
    'wireClick' => null,
    'class' => '',
])

@php
    $trClasses = '';

    if ($hover) {
        $trClasses .= ' hover:bg-gray-50 dark:hover:bg-zinc-900/40 transition-colors duration-150';
    }

    if ($striped) {
        $trClasses .= ' even:bg-gray-50 dark:even:bg-zinc-900/30';
    }

    if ($selected) {
        $trClasses .= ' bg-[#EFF6FF] dark:bg-zinc-900 border-l-4 border-[#2563EB]';
    }

    if ($clickable || $url || $wireClick) {
        $trClasses .= ' cursor-pointer';
    }

    $trClasses .= ' ' . $class;
@endphp

<tr
    class="{{ $trClasses }}"
    role="row"
    @if($url)
        onclick="window.location.href='{{ $url }}'"
    @elseif($wireClick)
        wire:click="{{ $wireClick }}"
    @elseif($clickable)
        onclick="{{ $attributes->get('onclick') }}"
    @endif
>
    {{ $slot }}
</tr>