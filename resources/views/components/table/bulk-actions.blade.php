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
<div class="flex items-center gap-2 p-4 bg-white dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700 {{ $class }}">
    {{-- Selection Info --}}
    <div class="flex items-center gap-2 text-sm text-[#231F20] dark:text-white">
        <flux:icon name="check-circle" class="h-5 w-5 text-[#10B981]" />
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
                        $buttonClasses .= ' text-white bg-[#2563EB] hover:bg-[#1D4ED8] focus:ring-[#2563EB]';
                        break;
                    case 'danger':
                        $buttonClasses .= ' text-white bg-[#DC2626] hover:bg-[#B91C1C] focus:ring-[#DC2626]';
                        break;
                    case 'warning':
                        $buttonClasses .= ' text-white bg-[#F59E0B] hover:bg-[#D97706] focus:ring-[#F59E0B]';
                        break;
                    case 'success':
                        $buttonClasses .= ' text-white bg-[#10B981] hover:bg-[#059669] focus:ring-[#10B981]';
                        break;
                    default:
                        $buttonClasses .= ' text-[#231F20] dark:text-white bg-white dark:bg-zinc-800 border border-[#E6E8EB] dark:border-zinc-700 hover:bg-gray-50 dark:hover:bg-zinc-900 focus:ring-[#FFF200]';
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
            class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-[#9B9EA4] dark:text-zinc-400 hover:text-[#231F20] dark:hover:text-white transition-colors duration-150"
        >
            <flux:icon name="x-mark" class="h-4 w-4 mr-1.5" />
            Clear selection
        </button>
    </div>
</div>
@endif