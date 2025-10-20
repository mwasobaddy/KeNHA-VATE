@props([
    'user' => null,
    'collaborator' => null,
    'size' => 'md',
    'showStatus' => true,
    'showPermission' => false,
    'clickable' => false
])

@php
    $userData = $user ?: ($collaborator ? $collaborator->user : null);

    if (!$userData) {
        return;
    }

    $sizeClasses = [
        'xs' => 'w-6 h-6 text-xs',
        'sm' => 'w-8 h-8 text-sm',
        'md' => 'w-10 h-10 text-base',
        'lg' => 'w-12 h-12 text-lg',
        'xl' => 'w-16 h-16 text-xl',
    ];

    $statusIndicatorSizes = [
        'xs' => 'w-2 h-2 -bottom-0.5 -right-0.5',
        'sm' => 'w-2.5 h-2.5 -bottom-0.5 -right-0.5',
        'md' => 'w-3 h-3 -bottom-1 -right-1',
        'lg' => 'w-3.5 h-3.5 -bottom-1 -right-1',
        'xl' => 'w-4 h-4 -bottom-1.5 -right-1.5',
    ];

    $currentSize = $sizeClasses[$size] ?? $sizeClasses['md'];
    $statusSize = $statusIndicatorSizes[$size] ?? $statusIndicatorSizes['md'];

    $initials = strtoupper(substr($userData->name, 0, 1));
    $status = $collaborator ? $collaborator->status : 'active';
    $permission = $collaborator ? $collaborator->permissions : null;

    $statusColors = [
        'active' => 'bg-green-500',
        'pending' => 'bg-yellow-500',
        'removed' => 'bg-red-500',
    ];

    $permissionColors = [
        'read' => 'bg-gray-500',
        'comment' => 'bg-blue-500',
        'edit' => 'bg-purple-500',
        'admin' => 'bg-red-500',
    ];
@endphp

<div class="relative inline-block">
    @if($clickable)
        <button
            type="button"
            class="relative inline-flex items-center justify-center {{ $currentSize }} font-medium text-white rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 hover:ring-2 hover:ring-offset-2 hover:ring-blue-500 transition-all duration-200"
            style="background: linear-gradient(135deg, {{ \Illuminate\Support\Str::random(6) }} 0%, {{ \Illuminate\Support\Str::random(6) }} 100%);"
            title="{{ $userData->name }} {{ $showPermission && $permission ? '(' . ucfirst($permission) . ')' : '' }}"
        >
            {{ $initials }}
        </button>
    @else
        <div
            class="relative inline-flex items-center justify-center {{ $currentSize }} font-medium text-white rounded-full"
            style="background: linear-gradient(135deg, {{ \Illuminate\Support\Str::random(6) }} 0%, {{ \Illuminate\Support\Str::random(6) }} 100%);"
            title="{{ $userData->name }} {{ $showPermission && $permission ? '(' . ucfirst($permission) . ')' : '' }}"
        >
            {{ $initials }}
        </div>
    @endif

    <!-- Status Indicator -->
    @if($showStatus && $status !== 'active')
        <div class="absolute {{ $statusSize }} {{ $statusColors[$status] ?? 'bg-gray-400' }} border-2 border-white rounded-full bottom-0 right-0"></div>
    @endif

    <!-- Permission Indicator -->
    @if($showPermission && $permission)
        <div class="absolute -top-1 -right-1 w-4 h-4 {{ $permissionColors[$permission] ?? 'bg-gray-400' }} border border-white rounded-full flex items-center justify-center">
            @if($permission === 'admin')
                <flux:icon name="shield" class="w-2.5 h-2.5 text-white" />
            @elseif($permission === 'edit')
                <flux:icon name="pencil" class="w-2.5 h-2.5 text-white" />
            @elseif($permission === 'comment')
                <flux:icon name="chat-bubble-left" class="w-2.5 h-2.5 text-white" />
            @else
                <flux:icon name="eye" class="w-2.5 h-2.5 text-white" />
            @endif
        </div>
    @endif
</div>