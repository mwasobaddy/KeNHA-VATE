@props([
    'status' => 'pending',
    'type' => null,
    'size' => 'sm',
    'showType' => true
])

@php
    $statusConfig = [
        'pending' => ['color' => 'yellow', 'icon' => 'clock', 'label' => 'Pending'],
        'accepted' => ['color' => 'green', 'icon' => 'check-circle', 'label' => 'Accepted'],
        'rejected' => ['color' => 'red', 'icon' => 'x-circle', 'label' => 'Rejected'],
    ];

    $typeConfig = [
        'author' => ['color' => 'blue', 'icon' => 'user', 'label' => 'Author'],
        'collaborator' => ['color' => 'purple', 'icon' => 'users', 'label' => 'Collaborator'],
        'rollback' => ['color' => 'orange', 'icon' => 'arrow-uturn-left', 'label' => 'Rollback'],
    ];

    $currentStatus = $statusConfig[$status] ?? $statusConfig['pending'];
    $currentType = $type ? ($typeConfig[$type] ?? null) : null;

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

@if($currentType && $showType)
    <div class="inline-flex items-center space-x-1">
        <!-- Status Badge -->
        <span class="inline-flex items-center space-x-1 {{ $sizeClasses[$size] }} font-medium rounded-full
            @if($currentStatus['color'] === 'yellow') bg-yellow-100 text-yellow-800
            @elseif($currentStatus['color'] === 'green') bg-green-100 text-green-800
            @elseif($currentStatus['color'] === 'red') bg-red-100 text-red-800
            @elseif($currentStatus['color'] === 'blue') bg-blue-100 text-blue-800
            @elseif($currentStatus['color'] === 'purple') bg-purple-100 text-purple-800
            @elseif($currentStatus['color'] === 'orange') bg-orange-100 text-orange-800
            @else bg-gray-100 text-gray-800 @endif">
            <flux:icon name="{{ $currentStatus['icon'] }}" class="{{ $iconSizes[$size] }}" />
            <span>{{ $currentStatus['label'] }}</span>
        </span>

        <!-- Type Badge -->
        <span class="inline-flex items-center space-x-1 {{ $sizeClasses[$size] }} font-medium rounded-full
            @if($currentType['color'] === 'yellow') bg-yellow-100 text-yellow-800
            @elseif($currentType['color'] === 'green') bg-green-100 text-green-800
            @elseif($currentType['color'] === 'red') bg-red-100 text-red-800
            @elseif($currentType['color'] === 'blue') bg-blue-100 text-blue-800
            @elseif($currentType['color'] === 'purple') bg-purple-100 text-purple-800
            @elseif($currentType['color'] === 'orange') bg-orange-100 text-orange-800
            @else bg-gray-100 text-gray-800 @endif">
            <flux:icon name="{{ $currentType['icon'] }}" class="{{ $iconSizes[$size] }}" />
            <span>{{ $currentType['label'] }}</span>
        </span>
    </div>
@else
    <!-- Status Only Badge -->
    <span class="inline-flex items-center space-x-1 {{ $sizeClasses[$size] }} font-medium rounded-full
        @if($currentStatus['color'] === 'yellow') bg-yellow-100 text-yellow-800
        @elseif($currentStatus['color'] === 'green') bg-green-100 text-green-800
        @elseif($currentStatus['color'] === 'red') bg-red-100 text-red-800
        @elseif($currentStatus['color'] === 'blue') bg-blue-100 text-blue-800
        @elseif($currentStatus['color'] === 'purple') bg-purple-100 text-purple-800
        @elseif($currentStatus['color'] === 'orange') bg-orange-100 text-orange-800
        @else bg-gray-100 text-gray-800 @endif">
        <flux:icon name="{{ $currentStatus['icon'] }}" class="{{ $iconSizes[$size] }}" />
        <span>{{ $currentStatus['label'] }}</span>
    </span>
@endif