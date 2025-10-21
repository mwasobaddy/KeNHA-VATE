<?php

use function Livewire\Volt\{state, computed, mount, on};
use App\Models\Idea;
use App\Services\RevisionService;

state([
    'idea' => null,
    'showModal' => false,
    'suggestedChanges' => [],
    'suggestionSummary' => '',
]);

mount(function (Idea $idea) {
    $this->idea = $idea;
});

on(['open-suggest-modal' => function () {
    $this->showModal = true;
}]);

$closeModal = function () {
    $this->showModal = false;
    $this->reset(['suggestedChanges', 'suggestionSummary']);
};

$suggestRevision = function () {
    $this->authorize('create_revisions');

    $validated = $this->validate([
        'suggestedChanges' => 'required|array|min:1',
        'suggestedChanges.*' => 'required|string|max:1000',
        'suggestionSummary' => 'required|string|max:500',
    ], [
        'suggestedChanges.required' => 'Please provide at least one suggested change.',
        'suggestedChanges.*.required' => 'Each suggested change must have content.',
        'suggestionSummary.required' => 'Please provide a summary of your suggestions.',
    ]);

    try {
        // Filter out empty changes
        $changes = array_filter($validated['suggestedChanges'], function ($change) {
            return !empty(trim($change));
        });

        if (empty($changes)) {
            $this->addError('suggestedChanges', 'Please provide at least one non-empty suggested change.');
            return;
        }

        app(RevisionService::class)->createRevision(
            $this->idea,
            $changes,
            $validated['suggestionSummary'],
            auth()->user(),
            'collaborator'
        );

        $this->reset(['showModal', 'suggestedChanges', 'suggestionSummary']);
        session()->flash('success', 'Your revision suggestion has been submitted successfully! The idea author will review it.');

        // Emit event to refresh parent component
        $this->dispatch('revision-suggested');
    } catch (\Exception $e) {
        session()->flash('error', 'Failed to submit revision suggestion: ' . $e->getMessage());
    }
};

$removeSuggestedChange = function ($index) {
    if (isset($this->suggestedChanges[$index])) {
        unset($this->suggestedChanges[$index]);
        $this->suggestedChanges = array_values($this->suggestedChanges); // Reindex array
    }
};

$addSuggestedChange = function () {
    $this->suggestedChanges[] = '';
};

?>

<div>
<!-- Suggest Edit Modal -->
@if($showModal)
<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" wire:click.self="closeModal">
    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl max-w-2xl w-full mx-4 p-8 relative">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Suggest Improvements</flux:heading>
                <flux:subheading>
                    Help improve this idea by suggesting specific changes or enhancements.
                    Your suggestions will be reviewed by the idea author.
                </flux:subheading>
            </div>

            <form wire:submit.prevent="suggestRevision" class="space-y-6">
                <!-- Suggestion Summary -->
                <div class="space-y-2">
                    <flux:label for="suggestionSummary">Summary of Changes</flux:label>
                    <flux:textarea
                        wire:model="suggestionSummary"
                        id="suggestionSummary"
                        placeholder="Briefly describe the overall changes you're suggesting..."
                        rows="3"
                        required
                    />
                    <flux:error name="suggestionSummary" />
                </div>

                <!-- Suggested Changes -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <flux:label>Specific Changes</flux:label>
                        <flux:button
                            type="button"
                            wire:click="addSuggestedChange"
                            variant="outline"
                        >
                            <flux:icon name="plus" class="w-4 h-4 mr-2" />
                            Add Change
                        </flux:button>
                    </div>

                    <div class="space-y-3">
                        @if(count($suggestedChanges) > 0)
                            @foreach($suggestedChanges as $index => $change)
                                <div class="flex items-start space-x-3">
                                    <div class="flex-1">
                                        <flux:textarea
                                            wire:model="suggestedChanges.{{ $index }}"
                                            placeholder="Describe a specific change you suggest..."
                                            rows="2"
                                            required
                                        />
                                        @error("suggestedChanges.{$index}")
                                            <flux:error>{{ $message }}</flux:error>
                                        @enderror
                                    </div>
                                    <flux:button
                                        type="button"
                                        wire:click="removeSuggestedChange({{ $index }})"
                                        variant="ghost"
                                        size="sm"
                                        class="text-red-600 hover:text-red-700 mt-1"
                                    >
                                        <flux:icon name="trash" class="w-4 h-4" />
                                    </flux:button>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-8 text-[#9B9EA4] dark:text-zinc-400 border-2 border-dashed border-[#9B9EA4]/20 dark:border-zinc-700 rounded-lg">
                                <flux:icon name="document-plus" class="w-8 h-8 mx-auto mb-2 text-gray-300" />
                                <p class="text-sm">No changes added yet</p>
                                <p class="text-xs">Click "Add Change" to start suggesting improvements</p>
                            </div>
                        @endif
                    </div>
                    <flux:error name="suggestedChanges" />
                </div>

                <!-- Submit Buttons -->
                <div class="flex justify-end space-x-3 pt-4 border-t border-[#9B9EA4]/20 dark:border-zinc-700">
                    <flux:button
                        type="button"
                        wire:click="closeModal"
                        variant="ghost"
                    >
                        Cancel
                    </flux:button>
                    @if(count($suggestedChanges) > 0)
                        <flux:button
                            type="submit"
                            variant="primary"
                        >
                            <flux:icon name="paper-airplane" class="w-4 h-4 mr-2" />
                            Submit Suggestion
                        </flux:button>
                    @else
                        <flux:button
                            type="button"
                            variant="primary"
                            disabled
                        >
                            <flux:icon name="paper-airplane" class="w-4 h-4 mr-2" />
                            Submit Suggestion
                        </flux:button>
                    @endif
                </div>
            </form>
        </div>

        <!-- Close button (top right) -->
        <button 
            type="button"
            wire:click="closeModal" 
            class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 dark:hover:text-white"
        >
            <flux:icon name="x-mark" class="w-6 h-6" />
        </button>
    </div>
</div>
@endif
</div>