<?php

use function Livewire\Volt\{state, computed, mount};
use App\Models\Idea;
use App\Services\RevisionService;

state([
    'ideaId' => null,
    'selectedRevision' => null,
    'comparisonRevision' => null,
    'showRejectForm' => false,
    'rejectReason' => '',
    'showRollbackConfirm' => false,
    'rollbackToRevision' => null,
]);

$idea = computed(function () {
    return Idea::with(['revisions' => function ($query) {
        $query->with('createdByUser')->orderBy('revision_number', 'desc');
    }])->findOrFail($this->ideaId);
});

$pendingRevisions = computed(function () {
    return $this->idea->revisions->where('status', 'pending');
});

$acceptedRevisions = computed(function () {
    return $this->idea->revisions->where('status', 'accepted');
});

$rejectedRevisions = computed(function () {
    return $this->idea->revisions->where('status', 'rejected');
});

$revisionStats = computed(function () {
    return [
        'total_revisions' => $this->idea->revisions->count(),
        'pending_revisions' => $this->pendingRevisions->count(),
        'accepted_revisions' => $this->acceptedRevisions->count(),
        'rejected_revisions' => $this->rejectedRevisions->count(),
    ];
});

mount(function (Idea $idea) {
    $this->ideaId = $idea->id;
});

$acceptRevision = function ($revisionId) {
    $this->authorize('manage_revisions');

    $revision = $this->idea->revisions()->findOrFail($revisionId);

    try {
        app(RevisionService::class)->acceptRevision($revision, auth()->user());
        session()->flash('success', 'Revision accepted successfully.');
        unset($this->idea);
    } catch (\Exception $e) {
        session()->flash('error', 'Failed to accept revision: ' . $e->getMessage());
    }
};

$rejectRevision = function ($revisionId) {
    $this->authorize('manage_revisions');

    $validated = $this->validate([
        'rejectReason' => 'nullable|string|max:500',
    ]);

    $revision = $this->idea->revisions()->findOrFail($revisionId);

    try {
        app(RevisionService::class)->rejectRevision($revision, auth()->user(), $validated['rejectReason']);
        $this->reset(['showRejectForm', 'rejectReason']);
        session()->flash('success', 'Revision rejected.');
        unset($this->idea);
    } catch (\Exception $e) {
        session()->flash('error', 'Failed to reject revision: ' . $e->getMessage());
    }
};

$rollbackToRevision = function ($revisionNumber) {
    $this->authorize('manage_revisions');

    try {
        app(RevisionService::class)->rollbackToRevision($this->idea, $revisionNumber, auth()->user());
        $this->reset(['showRollbackConfirm', 'rollbackToRevision']);
        session()->flash('success', 'Successfully rolled back to revision ' . $revisionNumber);
        unset($this->idea);
    } catch (\Exception $e) {
        session()->flash('error', 'Failed to rollback: ' . $e->getMessage());
    }
};

$compareRevisions = function ($revisionId1, $revisionId2) {
    $revision1 = $this->idea->revisions()->findOrFail($revisionId1);
    $revision2 = $this->idea->revisions()->findOrFail($revisionId2);

    try {
        $comparison = app(RevisionService::class)->compareRevisions($revision1, $revision2);
        $this->selectedRevision = $comparison;
    } catch (\Exception $e) {
        session()->flash('error', 'Failed to compare revisions: ' . $e->getMessage());
    }
};

$canManageRevisions = computed(function () {
    return auth()->user()->can('manage_revisions') && $this->idea->user_id === auth()->id();
});

$canCreateRevisions = computed(function () {
    return auth()->user()->can('create_revisions') &&
           ($this->idea->user_id === auth()->id() ||
            $this->idea->isCollaborator(auth()->user()));
});

?>

<div>
    <!-- Revision Stats -->
    <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Revision Overview</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="text-center">
                <div class="text-2xl font-bold text-blue-600">{{ $this->revisionStats['total_revisions'] }}</div>
                <div class="text-sm text-gray-500">Total Revisions</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-yellow-600">{{ $this->revisionStats['pending_revisions'] }}</div>
                <div class="text-sm text-gray-500">Pending</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-green-600">{{ $this->revisionStats['accepted_revisions'] }}</div>
                <div class="text-sm text-gray-500">Accepted</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-red-600">{{ $this->revisionStats['rejected_revisions'] }}</div>
                <div class="text-sm text-gray-500">Rejected</div>
            </div>
        </div>
    </div>

    <!-- Pending Revisions -->
    @if($this->pendingRevisions->count() > 0 && $this->canManageRevisions)
        <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Pending Revisions</h3>
            <div class="space-y-4">
                @foreach($this->pendingRevisions as $revision)
                    <div class="border rounded-lg p-4">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 mb-2">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">
                                        Revision {{ $revision->revision_number }}
                                    </span>
                                    <span class="text-sm text-gray-500">
                                        by {{ $revision->createdByUser->name ?? 'Unknown' }}
                                        {{ $revision->created_at->diffForHumans() }}
                                    </span>
                                </div>
                                @if($revision->change_summary)
                                    <p class="text-gray-900 font-medium mb-2">{{ $revision->change_summary }}</p>
                                @endif
                                <div class="text-sm text-gray-600">
                                    <span class="font-medium">{{ is_array($revision->changed_fields) ? count($revision->changed_fields) : 0 }}</span> field{{ (is_array($revision->changed_fields) ? count($revision->changed_fields) : 0) !== 1 ? 's' : '' }} changed
                                </div>
                            </div>
                            <div class="flex space-x-2 ml-4">
                                <flux:button
                                    wire:click="acceptRevision({{ $revision->id }})"
                                    variant="primary"
                                    size="sm"
                                >
                                    Accept
                                </flux:button>
                                <flux:button
                                    wire:click="$set('showRejectForm', {{ $revision->id }})"
                                    variant="danger"
                                    size="sm"
                                >
                                    Reject
                                </flux:button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Revision Timeline -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Revision History</h3>
            @if($this->idea->revisions->count() > 1)
                <flux:button
                    wire:click="$set('selectedRevision', 'compare')"
                    variant="outline"
                    size="sm"
                >
                    Compare Revisions
                </flux:button>
            @endif
        </div>

        @if($this->idea->revisions->count() > 0)
            <div class="space-y-4">
                @foreach($this->idea->revisions as $revision)
                    <div class="flex items-start space-x-4">
                        <!-- Timeline line -->
                        <div class="flex flex-col items-center">
                            <div class="w-3 h-3 rounded-full
                                @if($revision->status === 'accepted') bg-green-500
                                @elseif($revision->status === 'rejected') bg-red-500
                                @elseif($revision->status === 'pending') bg-yellow-500
                                @else bg-gray-400 @endif">
                            </div>
                            @if(!$loop->last)
                                <div class="w-px h-12 bg-gray-200 mt-2"></div>
                            @endif
                        </div>

                        <!-- Revision content -->
                        <div class="flex-1 pb-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <span class="font-medium text-gray-900">
                                        Revision {{ $revision->revision_number }}
                                    </span>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full
                                        @if($revision->status === 'accepted') bg-green-100 text-green-800
                                        @elseif($revision->status === 'rejected') bg-red-100 text-red-800
                                        @elseif($revision->status === 'pending') bg-yellow-100 text-yellow-800
                                        @else bg-gray-100 text-gray-800 @endif">
                                        {{ ucfirst($revision->status) }}
                                    </span>
                                    <span class="text-sm text-gray-500">
                                        {{ $revision->revision_type === 'author' ? 'Author' : 'Collaborator' }}
                                    </span>
                                </div>

                                @if($this->canManageRevisions && $revision->status === 'accepted' && $revision->revision_number < $this->idea->current_revision_number)
                                    <flux:button
                                        wire:click="$set('rollbackToRevision', {{ $revision->revision_number }})"
                                        variant="outline"
                                        size="sm"
                                    >
                                        Rollback
                                    </flux:button>
                                @endif
                            </div>

                            <div class="mt-2 text-sm text-gray-600">
                                <span class="font-medium">{{ $revision->createdByUser->name ?? 'Unknown User' }}</span>
                                <span class="mx-2">•</span>
                                <span>{{ $revision->created_at->format('M j, Y \a\t g:i A') }}</span>
                            </div>

                            @if($revision->change_summary)
                                <p class="mt-2 text-gray-900">{{ $revision->change_summary }}</p>
                            @endif

                            @if($revision->status === 'rejected' && $revision->review_reason)
                                <div class="mt-2 p-3 bg-red-50 rounded-lg">
                                    <p class="text-sm text-red-800">
                                        <strong>Rejection Reason:</strong> {{ $revision->review_reason }}
                                    </p>
                                </div>
                            @endif

                            @if(is_array($revision->changed_fields) && count($revision->changed_fields) > 0)
                                <div class="mt-2 text-sm text-gray-500">
                                    Changed fields: {{ implode(', ', array_keys($revision->changed_fields)) }}
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8 text-gray-500">
                <flux:icon name="document-text" class="w-12 h-12 mx-auto mb-4 text-gray-300" />
                <p>No revisions yet</p>
                <p class="text-sm">Revisions will appear here when collaborators suggest changes</p>
            </div>
        @endif
    </div>

    <!-- Reject Revision Modal -->
    <flux:modal wire:model="showRejectForm" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Reject Revision</flux:heading>
                <flux:subheading>Provide a reason for rejecting this revision</flux:subheading>
            </div>

            <form wire:submit="rejectRevision({{ $showRejectForm }})" class="space-y-6">
                <div class="space-y-2">
                    <flux:label for="rejectReason">Reason (Optional)</flux:label>
                    <flux:textarea
                        wire:model="rejectReason"
                        id="rejectReason"
                        placeholder="Please explain why this revision was rejected..."
                        rows="3"
                    />
                    <flux:error name="rejectReason" />
                </div>

                <div class="flex justify-end space-x-2">
                    <flux:button
                        wire:click="$set('showRejectForm', false)"
                        variant="ghost"
                    >
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="danger">
                        Reject Revision
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Rollback Confirmation Modal -->
    <flux:modal wire:model="rollbackToRevision" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Confirm Rollback</flux:heading>
                <flux:subheading>
                    Are you sure you want to rollback to revision {{ $rollbackToRevision }}?
                    This will undo all changes made after this revision.
                </flux:subheading>
            </div>

            <div class="flex justify-end space-x-2">
                <flux:button
                    wire:click="$set('rollbackToRevision', null)"
                    variant="ghost"
                >
                    Cancel
                </flux:button>
                <flux:button
                    wire:click="rollbackToRevision({{ $rollbackToRevision }})"
                    variant="danger"
                >
                    Rollback to Revision {{ $rollbackToRevision }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Revision Comparison Modal -->
    <flux:modal wire:model="selectedRevision" class="max-w-4xl">
        @if($selectedRevision === 'compare')
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Compare Revisions</flux:heading>
                    <flux:subheading>Select two revisions to compare</flux:subheading>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <flux:label>Revision 1</flux:label>
                        <flux:select wire:model="comparisonRevision">
                            <option value="">Select revision...</option>
                            @foreach($this->idea->revisions as $revision)
                                <option value="{{ $revision->id }}">
                                    Revision {{ $revision->revision_number }} - {{ $revision->createdByUser->name ?? 'Unknown' }}
                                </option>
                            @endforeach
                        </flux:select>
                    </div>
                    <div class="space-y-2">
                        <flux:label>Revision 2</flux:label>
                        <flux:select wire:model="comparisonRevision">
                            <option value="">Select revision...</option>
                            @foreach($this->idea->revisions as $revision)
                                <option value="{{ $revision->id }}">
                                    Revision {{ $revision->revision_number }} - {{ $revision->createdByUser->name ?? 'Unknown' }}
                                </option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>

                <div class="flex justify-end">
                    <flux:button
                        wire:click="$set('selectedRevision', null)"
                        variant="ghost"
                    >
                        Close
                    </flux:button>
                </div>
            </div>
        @elseif(is_array($selectedRevision))
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Revision Comparison</flux:heading>
                    <flux:subheading>
                        Comparing Revision {{ $selectedRevision['revision_1']['number'] }} with Revision {{ $selectedRevision['revision_2']['number'] }}
                    </flux:subheading>
                </div>

                <div class="space-y-4">
                    @if(empty($selectedRevision['differences']))
                        <div class="text-center py-8 text-gray-500">
                            <p>No differences found between these revisions</p>
                        </div>
                    @else
                        @foreach($selectedRevision['differences'] as $field => $changes)
                            <div class="border rounded-lg p-4">
                                <h4 class="font-medium text-gray-900 mb-2">{{ ucfirst(str_replace('_', ' ', $field)) }}</h4>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="p-3 bg-red-50 rounded-lg">
                                        <div class="text-sm font-medium text-red-800 mb-1">Revision {{ $selectedRevision['revision_1']['number'] }}</div>
                                        <div class="text-sm text-red-700">{{ $changes['revision_' . $selectedRevision['revision_1']['number']] ?? 'Not set' }}</div>
                                    </div>
                                    <div class="p-3 bg-green-50 rounded-lg">
                                        <div class="text-sm font-medium text-green-800 mb-1">Revision {{ $selectedRevision['revision_2']['number'] }}</div>
                                        <div class="text-sm text-green-700">{{ $changes['revision_' . $selectedRevision['revision_2']['number']] ?? 'Not set' }}</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                <div class="flex justify-end">
                    <flux:button
                        wire:click="$set('selectedRevision', null)"
                        variant="ghost"
                    >
                        Close
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>