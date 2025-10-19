{{-- Card Body Component --}}
@props([
    'content' => null,
    'class' => '',
])

@php
    $bodyClasses = 'px-6 py-4 ' . $class;
@endphp

<div class="{{ $bodyClasses }}">
    @if($content)
        <div class="text-sm text-[#231F20] dark:text-zinc-200 leading-relaxed">
            {!! $content !!}
        </div>
    @else
        {{ $slot }}
    @endif
</div>