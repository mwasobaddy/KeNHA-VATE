@props([
    'alt' => 'Kenya National Highways Authority Logo',
    'class' => 'h-full w-full object-cover',
])

<img src="{{ asset('kenya-national-highways-authority-kenha-logo-png_seeklogo-341630.png') }}" 
     alt="{{ $alt }}" 
     class="{{ $class }} hover:scale-105 transition-transform duration-300 ease-in-out h-full w-full object-cover" 
     {{ $attributes }}>