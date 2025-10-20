@props([
    'permission' => 'read',
    'showLabel' => true,
    'size' => 'sm',
    'variant' => 'badge' // badge, icon, text
])

@php
    $permissionConfig = [
        'read' => [
            'label' => 'Read Only',
            'description' => 'Can view the idea and its revisions',
            'icon' => 'eye',
            'color' => 'gray',
            'bgColor' => 'bg-gray-100',
            'textColor' => 'text-gray-800'
        ],
        'comment' => [
            'label' => 'Read & Comment',
            'description' => 'Can view, comment, and suggest revisions',
            'icon' => 'chat-bubble-left',
            'color' => 'blue',
            'bgColor' => 'bg-blue-100',
            'textColor' => 'text-blue-800'
        ],
        'edit' => [
            'label' => 'Read & Suggest Edits',
            'description' => 'Can view, comment, and suggest detailed revisions',
            'icon' => 'pencil',
            'color' => 'purple',
            'bgColor' => 'bg-purple-100',
            'textColor' => 'text-purple-800'
        ],
        'admin' => [
            'label' => 'Full Access',
            'description' => 'Can manage collaborators and make changes',
            'icon' => 'shield',
            'color' => 'red',
            'bgColor' => 'bg-red-100',
            'textColor' => 'text-red-800'
        ],
    ];

    $config = $permissionConfig[$permission] ?? $permissionConfig['read'];

    $sizeClasses = [
        'xs' => 'px-1.5 py-0.5 text-xs',
        'sm' => 'px-2 py-1 text-xs',
        'md' => 'px-2.5 py-1.5 text-sm',
        'lg' => 'px-3 py-2 text-sm',
    ];

    $iconSizes = [
        'xs' => 'w-3 h-3',
        'sm' => 'w-3 h-3',
        'md' => 'w-4 h-4',
        'lg' => 'w-4 h-4',
    ];
@endphp

@if($variant === 'badge')
    <!-- Badge Variant -->
    <span class="inline-flex items-center space-x-1 {{ $sizeClasses[$size] }} font-medium rounded-full {{ $config['bgColor'] }} {{ $config['textColor'] }}"
          title="{{ $config['description'] }}">
        <flux:icon name="{{ $config['icon'] }}" class="{{ $iconSizes[$size] }}" />
        @if($showLabel)
            <span>{{ $config['label'] }}</span>
        @endif
    </span>

@elseif($variant === 'icon')
    <!-- Icon Only Variant -->
    <div class="relative group">
        <flux:icon name="{{ $config['icon'] }}" class="{{ $iconSizes[$size] }} {{ $config['textColor'] }}" />
        @if($showLabel)
            <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 text-xs font-medium text-white bg-gray-900 rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 whitespace-nowrap z-10">
                {{ $config['label'] }}
                <div class="absolute top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-gray-900"></div>
            </div>
        @endif
    </div>

@else
    <!-- Text Only Variant -->
    <span class="{{ $config['textColor'] }} font-medium" title="{{ $config['description'] }}">
        {{ $showLabel ? $config['label'] : '' }}
    </span>
@endif