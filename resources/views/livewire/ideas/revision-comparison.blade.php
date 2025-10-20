<?php

use function Livewire\Volt\{state, computed, mount};
use App\Models\Idea;
use App\Services\RevisionService;

state([
    'revisionId1' => null,
    'revisionId2' => null,
    'comparison' => null,
]);

$idea = computed(function () {
    return Idea::with(['revisions' => function ($query) {
        $query->with('createdByUser')->orderBy('revision_number', 'desc');
    }])->findOrFail($this->ideaId);
});

$availableRevisions = computed(function () {
    return $this->idea->revisions->map(function ($revision) {
        return [
            'id' => $revision->id,
            'number' => $revision->revision_number,
            'label' => "Revision {$revision->revision_number} - {$revision->createdByUser->name ?? 'Unknown'} ({$revision->created_at->format('M j, Y')})",
            'created_by' => $revision->createdByUser->name ?? 'Unknown',
            'created_at' => $revision->created_at,
            'status' => $revision->status,
            'type' => $revision->revision_type,
        ];
    });
});

mount(function () {
    // If revision IDs are provided, load the comparison
    if ($this->revisionId1 && $this->revisionId2) {
        $this->loadComparison();
    }
});

$loadComparison = function () {
    if (!$this->revisionId1 || !$this->revisionId2) {
        $this->comparison = null;
        return;
    }

    try {
        $revision1 = $this->idea->revisions()->findOrFail($this->revisionId1);
        $revision2 = $this->idea->revisions()->findOrFail($this->revisionId2);

        $this->comparison = app(RevisionService::class)->compareRevisions($revision1, $revision2);
    } catch (\Exception $e) {
        session()->flash('error', 'Failed to compare revisions: ' . $e->getMessage());
        $this->comparison = null;
    }
};

$swapRevisions = function () {
    [$this->revisionId1, $this->revisionId2] = [$this->revisionId2, $this->revisionId1];
    $this->loadComparison();
};

$canViewRevisions = computed(function () {
    return auth()->user()->can('view_collaboration_activity') &&
           ($this->idea->user_id === auth()->id() ||
            $this->idea->isCollaborator(auth()->user()));
});

$formatFieldValue = function ($value) {
    if (is_array($value)) {
        return implode(', ', $value);
    }
    if (is_bool($value)) {
        return $value ? 'Yes' : 'No';
    }
    return $value ?? 'Not set';
};

$hasDifferences = computed(function () {
    return $this->comparison && !empty($this->comparison['differences']);
});

?>

<div>
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Revision Comparison</h1>
                <p class="text-gray-600 mt-1">Compare changes between different revisions</p>
            </div>
            @if($comparison)
                <flux:button
                    wire:click="swapRevisions"
                    variant="outline"
                    size="sm"
                >
                    <flux:icon name="arrow-left-right" class="w-4 h-4 mr-2" />
                    Swap
                </flux:button>
            @endif
        </div>
    </div>

    @if(!$canViewRevisions)
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="flex">
                <flux:icon name="exclamation-triangle" class="w-5 h-5 text-yellow-400" />
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">Access Restricted</h3>
                    <p class="text-sm text-yellow-700 mt-1">
                        You don't have permission to view revision comparisons.
                    </p>
                </div>
            </div>
        </div>
    @else
        <!-- Revision Selector -->
        <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Select Revisions to Compare</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <flux:label for="revisionId1">Revision 1</flux:label>
                    <flux:select
                        wire:model.live="revisionId1"
                        id="revisionId1"
                        wire:change="loadComparison"
                    >
                        <option value="">Select first revision...</option>
                        @foreach($availableRevisions as $revision)
                            <option value="{{ $revision['id'] }}">{{ $revision['label'] }}</option>
                        @endforeach
                    </flux:select>
                    @if($revisionId1)
                        @php $rev1 = collect($availableRevisions)->firstWhere('id', $revisionId1) @endphp
                        @if($rev1)
                            <div class="text-sm text-gray-600 mt-1">
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    @if($rev1['status'] === 'accepted') bg-green-100 text-green-800
                                    @elseif($rev1['status'] === 'rejected') bg-red-100 text-red-800
                                    @elseif($rev1['status'] === 'pending') bg-yellow-100 text-yellow-800
                                    @else bg-gray-100 text-gray-800 @endif">
                                    {{ ucfirst($rev1['status']) }}
                                </span>
                                <span class="ml-2">{{ $rev1['type'] === 'author' ? 'Author' : 'Collaborator' }}</span>
                            </div>
                        @endif
                    @endif
                </div>

                <div class="space-y-2">
                    <flux:label for="revisionId2">Revision 2</flux:label>
                    <flux:select
                        wire:model.live="revisionId2"
                        id="revisionId2"
                        wire:change="loadComparison"
                    >
                        <option value="">Select second revision...</option>
                        @foreach($availableRevisions as $revision)
                            <option value="{{ $revision['id'] }}">{{ $revision['label'] }}</option>
                        @endforeach
                    </flux:select>
                    @if($revisionId2)
                        @php $rev2 = collect($availableRevisions)->firstWhere('id', $revisionId2) @endphp
                        @if($rev2)
                            <div class="text-sm text-gray-600 mt-1">
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    @if($rev2['status'] === 'accepted') bg-green-100 text-green-800
                                    @elseif($rev2['status'] === 'rejected') bg-red-100 text-red-800
                                    @elseif($rev2['status'] === 'pending') bg-yellow-100 text-yellow-800
                                    @else bg-gray-100 text-gray-800 @endif">
                                    {{ ucfirst($rev2['status']) }}
                                </span>
                                <span class="ml-2">{{ $rev2['type'] === 'author' ? 'Author' : 'Collaborator' }}</span>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        <!-- Comparison Results -->
        @if($comparison)
            <div class="bg-white rounded-lg shadow-sm border p-6">
                @if($hasDifferences)
                    <div class="mb-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-2">Changes Found</h2>
                        <p class="text-gray-600">
                            Comparing Revision {{ $comparison['revision_1']['number'] }} with Revision {{ $comparison['revision_2']['number'] }}
                        </p>
                    </div>

                    <div class="space-y-6">
                        @foreach($comparison['differences'] as $field => $changes)
                            <div class="border rounded-lg overflow-hidden">
                                <div class="bg-gray-50 px-4 py-3 border-b">
                                    <h3 class="font-medium text-gray-900">
                                        {{ ucfirst(str_replace('_', ' ', $field)) }}
                                    </h3>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-0">
                                    <!-- Revision 1 -->
                                    <div class="p-4 border-r bg-red-50">
                                        <div class="flex items-center mb-2">
                                            <div class="w-3 h-3 rounded-full bg-red-500 mr-2"></div>
                                            <span class="text-sm font-medium text-red-800">
                                                Revision {{ $comparison['revision_1']['number'] }}
                                            </span>
                                            <span class="text-xs text-red-600 ml-2">
                                                ({{ $comparison['revision_1']['created_by'] }})
                                            </span>
                                        </div>
                                        <div class="text-sm text-red-700 bg-white p-3 rounded border">
                                            {{ $formatFieldValue($changes['revision_' . $comparison['revision_1']['number']]) }}
                                        </div>
                                    </div>

                                    <!-- Revision 2 -->
                                    <div class="p-4 bg-green-50">
                                        <div class="flex items-center mb-2">
                                            <div class="w-3 h-3 rounded-full bg-green-500 mr-2"></div>
                                            <span class="text-sm font-medium text-green-800">
                                                Revision {{ $comparison['revision_2']['number'] }}
                                            </span>
                                            <span class="text-xs text-green-600 ml-2">
                                                ({{ $comparison['revision_2']['created_by'] }})
                                            </span>
                                        </div>
                                        <div class="text-sm text-green-700 bg-white p-3 rounded border">
                                            {{ $formatFieldValue($changes['revision_' . $comparison['revision_2']['number']]) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <flux:icon name="check-circle" class="w-16 h-16 mx-auto mb-4 text-green-500" />
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No Differences Found</h3>
                        <p class="text-gray-500">
                            These revisions are identical or no comparable changes were found.
                        </p>
                    </div>
                @endif
            </div>
        @else
            <!-- Empty State -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <div class="text-center py-12">
                    <flux:icon name="git-compare" class="w-16 h-16 mx-auto mb-4 text-gray-300" />
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Select Revisions to Compare</h3>
                    <p class="text-gray-500 mb-6">
                        Choose two revisions from the dropdowns above to see the differences between them.
                    </p>
                    @if($availableRevisions->count() < 2)
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 inline-block">
                            <p class="text-sm text-yellow-800">
                                <strong>Note:</strong> You need at least 2 revisions to compare changes.
                                Currently this idea has {{ $availableRevisions->count() }} revision{{ $availableRevisions->count() !== 1 ? 's' : '' }}.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    @endif
</div>