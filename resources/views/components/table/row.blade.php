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
        $trClasses .= ' hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors duration-150';
    }

    if ($striped) {
        $trClasses .= ' even:bg-gray-50 dark:even:bg-gray-800/30';
    }

    if ($selected) {
        $trClasses .= ' bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500';
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