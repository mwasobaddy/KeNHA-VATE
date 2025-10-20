@props([
    'idea' => null,
    'showLabel' => true,
    'size' => 'sm'
])

@php
    if (!$idea) {
        return;
    }

    $isEnabled = $idea->collaboration_enabled;
    $collaboratorCount = $idea->activeCollaborators->count();
    $pendingRequests = $idea->collaborationRequests->where('status', 'pending')->count();
    $hasDeadline = $idea->collaboration_deadline && $idea->collaboration_deadline->isFuture();

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

@if($isEnabled)
    <div class="inline-flex items-center space-x-{{ $showLabel ? '1' : '0' }}">
        <!-- Collaboration Enabled Badge -->
        <span class="inline-flex items-center space-x-1 {{ $sizeClasses[$size] }} font-medium rounded-full bg-green-100 text-green-800">
            <flux:icon name="users" class="{{ $iconSizes[$size] }}" />
            @if($showLabel)
                <span>Collaboration Open</span>
            @endif
        </span>

        <!-- Collaborator Count -->
        @if($collaboratorCount > 0)
            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                <flux:icon name="user-group" class="w-3 h-3 mr-1" />
                {{ $collaboratorCount }}
            </span>
        @endif

        <!-- Pending Requests -->
        @if($pendingRequests > 0)
            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">
                <flux:icon name="clock" class="w-3 h-3 mr-1" />
                {{ $pendingRequests }} pending
            </span>
        @endif

        <!-- Deadline Warning -->
        @if($hasDeadline)
            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-800">
                <flux:icon name="calendar" class="w-3 h-3 mr-1" />
                {{ $idea->collaboration_deadline->format('M j') }}
            </span>
        @endif
    </div>
@else
    <!-- Collaboration Disabled -->
    <span class="inline-flex items-center space-x-1 {{ $sizeClasses[$size] }} font-medium rounded-full bg-gray-100 text-gray-600">
        <flux:icon name="lock-closed" class="{{ $iconSizes[$size] }}" />
        @if($showLabel)
            <span>Collaboration Closed</span>
        @endif
    </span>
@endif