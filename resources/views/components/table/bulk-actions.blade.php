{{-- Table Bulk Actions Component --}}
@props([
    'selectedIds' => [],
    'actions' => [],
    'class' => '',
])

@php
    $count = count($selectedIds);
@endphp

@if($count > 0 && !empty($actions))
<div class="flex items-center gap-2 p-4 bg-blue-50 dark:bg-blue-900/20 border-b border-blue-200 dark:border-blue-800 {{ $class }}">
    {{-- Selection Info --}}
    <div class="flex items-center gap-2 text-sm text-blue-700 dark:text-blue-300">
        <flux:icon name="check-circle" class="h-5 w-5" />
        <span class="font-medium">{{ $count }} item{{ $count > 1 ? 's' : '' }} selected</span>
    </div>

    {{-- Action Buttons --}}
    <div class="flex items-center gap-2 ml-4">
        @foreach($actions as $action)
            @php
                $variant = $action['variant'] ?? 'secondary';
                $wireClick = $action['wireClick'] ?? null;
                $href = $action['href'] ?? null;
                $confirm = $action['confirm'] ?? null;
                $text = $action['text'] ?? $action['label'] ?? 'Action';
                $icon = $action['icon'] ?? null;
                $disabled = $action['disabled'] ?? false;

                $buttonClasses = 'inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-md transition-colors duration-150';

                switch($variant) {
                    case 'primary':
                        $buttonClasses .= ' text-white bg-blue-600 hover:bg-blue-700 focus:ring-blue-500';
                        break;
                    case 'danger':
                        $buttonClasses .= ' text-white bg-red-600 hover:bg-red-700 focus:ring-red-500';
                        break;
                    case 'warning':
                        $buttonClasses .= ' text-white bg-yellow-600 hover:bg-yellow-700 focus:ring-yellow-500';
                        break;
                    case 'success':
                        $buttonClasses .= ' text-white bg-green-600 hover:bg-green-700 focus:ring-green-500';
                        break;
                    default:
                        $buttonClasses .= ' text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 focus:ring-gray-500';
                }

                if ($disabled) {
                    $buttonClasses .= ' opacity-50 cursor-not-allowed';
                }
            @endphp

            @if($href && !$disabled)
                <a
                    href="{{ $href }}"
                    class="{{ $buttonClasses }} focus:outline-none focus:ring-2 focus:ring-offset-2"
                    @if($confirm)
                        onclick="return confirm('{{ $confirm }}')"
                    @endif
                >
                    @if($icon)
                        <flux:icon name="{{ $icon }}" class="h-4 w-4 mr-1.5" />
                    @endif
                    {{ $text }}
                </a>
            @elseif($wireClick && !$disabled)
                <button
                    wire:click="{{ $wireClick }}"
                    class="{{ $buttonClasses }} focus:outline-none focus:ring-2 focus:ring-offset-2"
                    @if($confirm)
                        wire:confirm="{{ $confirm }}"
                    @endif
                >
                    @if($icon)
                        <flux:icon name="{{ $icon }}" class="h-4 w-4 mr-1.5" />
                    @endif
                    {{ $text }}
                </button>
            @else
                <span class="{{ $buttonClasses }}">
                    @if($icon)
                        <flux:icon name="{{ $icon }}" class="h-4 w-4 mr-1.5" />
                    @endif
                    {{ $text }}
                </span>
            @endif
        @endforeach
    </div>

    {{-- Clear Selection --}}
    <div class="ml-auto">
        <button
            wire:click="clearSelection"
            class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition-colors duration-150"
        >
            <flux:icon name="x-mark" class="h-4 w-4 mr-1.5" />
            Clear selection
        </button>
    </div>
</div>
@endif