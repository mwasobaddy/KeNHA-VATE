{{-- Table Delete Confirmation Modal Component --}}
@props([
    'id' => 'delete-modal',
    'title' => 'Confirm Deletion',
    'message' => 'Are you sure you want to delete this item?',
    'confirmText' => 'Delete',
    'cancelText' => 'Cancel',
    'variant' => 'danger',
    'size' => 'md',
    'showSoftDelete' => true,
    'showPermanentDelete' => true,
    'softDeleteText' => 'Move to Trash',
    'permanentDeleteText' => 'Delete Permanently',
    'softDeleteDescription' => 'This item will be moved to trash and can be restored later.',
    'permanentDeleteDescription' => 'This action cannot be undone. This will permanently delete the item.',
    'model' => 'showDeleteModal',
    'wireSoftDelete' => null,
    'wirePermanentDelete' => null,
    'wireCancel' => null,
    'idea' => null, // Pass the idea object to determine delete options
])

@php
    $modalSize = match($size) {
        'sm' => 'max-w-md',
        'lg' => 'max-w-2xl',
        'xl' => 'max-w-4xl',
        default => 'max-w-lg'
    };

    // Determine delete options based on idea status
    $isDraft = $idea && $idea->status === 'draft';
    $isSingleIdea = $idea !== null;

    if ($isSingleIdea) {
        if ($isDraft) {
            // For draft ideas: show permanent delete as primary, soft delete as secondary
            $primaryAction = 'permanent';
            $primaryText = 'Delete Permanently';
            $primaryDescription = 'This draft idea will be permanently deleted and cannot be recovered.';
            $secondaryAction = 'soft';
            $secondaryText = 'Move to Trash';
            $secondaryDescription = 'This draft idea will be moved to trash (not recommended for drafts).';
        } else {
            // For non-draft ideas: show soft delete as primary, permanent delete as secondary
            $primaryAction = 'soft';
            $primaryText = 'Move to Trash';
            $primaryDescription = 'This idea will be moved to trash and can be restored later.';
            $secondaryAction = 'permanent';
            $secondaryText = 'Delete Permanently';
            $secondaryDescription = 'This action cannot be undone. The idea will be permanently deleted.';
        }
    } else {
        // For bulk operations, show both options
        $showSoftDelete = true;
        $showPermanentDelete = true;
    }
@endphp

<flux:modal name="{{ $id }}" wire:model="{{ $model }}" class="{{ $modalSize }}">
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex items-center gap-3">
            <div class="flex-shrink-0">
                <flux:icon name="exclamation-triangle" class="h-6 w-6 text-red-500" />
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-[#231F20] dark:text-white">
                    {{ $title }}
                </h3>
            </div>
        </div>

        {{-- Message --}}
        <div class="text-sm text-[#9B9EA4] dark:text-zinc-400">
            @if($isSingleIdea)
                Are you sure you want to delete "{{ $idea->idea_title }}"?
            @else
                {{ $message }}
            @endif
        </div>

        {{-- Delete Options --}}
        @if($isSingleIdea)
            {{-- Single Idea: Show primary and secondary options --}}
            <div class="space-y-3">
                {{-- Primary Action (recommended based on status) --}}
                <div class="flex items-start gap-3 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                    <flux:icon name="{{ $primaryAction === 'soft' ? 'archive-box' : 'trash' }}" class="h-5 w-5 text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0" />
                    <div class="flex-1">
                        <h4 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                            {{ $primaryText }} (Recommended)
                        </h4>
                        <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                            {{ $primaryDescription }}
                        </p>
                        <div class="mt-3">
                            <flux:button
                                wire:click="{{ $primaryAction === 'soft' ? $wireSoftDelete : $wirePermanentDelete }}"
                                variant="primary"
                                size="sm"
                                class="w-full sm:w-auto"
                            >
                                {{ $primaryText }}
                            </flux:button>
                        </div>
                    </div>
                </div>

                {{-- Secondary Action (alternative option) --}}
                <div class="flex items-start gap-3 p-4 bg-gray-50 dark:bg-gray-900/20 border border-gray-200 dark:border-gray-800 rounded-lg">
                    <flux:icon name="{{ $secondaryAction === 'soft' ? 'archive-box' : 'trash' }}" class="h-5 w-5 text-gray-600 dark:text-gray-400 mt-0.5 flex-shrink-0" />
                    <div class="flex-1">
                        <h4 class="text-sm font-medium text-gray-800 dark:text-gray-200">
                            {{ $secondaryText }}
                        </h4>
                        <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">
                            {{ $secondaryDescription }}
                        </p>
                        <div class="mt-3">
                            <flux:button
                                wire:click="{{ $secondaryAction === 'soft' ? $wireSoftDelete : $wirePermanentDelete }}"
                                variant="ghost"
                                size="sm"
                                class="w-full sm:w-auto"
                            >
                                {{ $secondaryText }}
                            </flux:button>
                        </div>
                    </div>
                </div>
            </div>
        @elseif($showSoftDelete || $showPermanentDelete)
            {{-- Bulk Operations: Show both options --}}
            <div class="space-y-3">
                <p class="text-sm font-medium text-[#231F20] dark:text-white">
                    Choose deletion type:
                </p>

                @if($showSoftDelete)
                    <div class="flex items-start gap-3 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                        <flux:icon name="archive-box" class="h-5 w-5 text-yellow-600 dark:text-yellow-400 mt-0.5 flex-shrink-0" />
                        <div class="flex-1">
                            <h4 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                {{ $softDeleteText }}
                            </h4>
                            <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                                {{ $softDeleteDescription }}
                            </p>
                            <div class="mt-3">
                                <flux:button
                                    wire:click="{{ $wireSoftDelete }}"
                                    variant="danger"
                                    size="sm"
                                    class="w-full sm:w-auto"
                                >
                                    {{ $softDeleteText }}
                                </flux:button>
                            </div>
                        </div>
                    </div>
                @endif

                @if($showPermanentDelete)
                    <div class="flex items-start gap-3 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                        <flux:icon name="trash" class="h-5 w-5 text-red-600 dark:text-red-400 mt-0.5 flex-shrink-0" />
                        <div class="flex-1">
                            <h4 class="text-sm font-medium text-red-800 dark:text-red-200">
                                {{ $permanentDeleteText }}
                            </h4>
                            <p class="text-sm text-red-700 dark:text-red-300 mt-1">
                                {{ $permanentDeleteDescription }}
                            </p>
                            <div class="mt-3">
                                <flux:button
                                    wire:click="{{ $wirePermanentDelete }}"
                                    variant="danger"
                                    size="sm"
                                    class="w-full sm:w-auto"
                                >
                                    {{ $permanentDeleteText }}
                                </flux:button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @else
            {{-- Single Action Button --}}
            <div class="flex justify-end gap-3">
                <flux:button
                    wire:click="{{ $wireCancel ?? '$set(\'' . $wireModel . '\', false)' }}"
                    variant="ghost"
                >
                    {{ $cancelText }}
                </flux:button>
                <flux:button
                    wire:click="{{ $wirePermanentDelete ?? $wireSoftDelete }}"
                    :variant="$variant"
                >
                    {{ $confirmText }}
                </flux:button>
            </div>
        @endif

        {{-- Footer with Cancel (only shown when delete options are available) --}}
        @if($showSoftDelete || $showPermanentDelete)
            <div class="flex justify-end pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <flux:button
                    wire:click="{{ $wireCancel ?? '$set(\'' . $wireModel . '\', false)' }}"
                    variant="ghost"
                >
                    {{ $cancelText }}
                </flux:button>
            </div>
        @endif
    </div>
</flux:modal>