<?php

use function Livewire\Volt\{state, computed, mount};
use App\Models\Idea;
use App\Models\IdeaCollaborationRequest;
use App\Services\CollaborationService;

state([
    'ideaId' => null,
    'showResponseForm' => false,
    'responseMessage' => '',
    'respondingToRequest' => null,
]);

$idea = computed(function () {
    return Idea::findOrFail($this->ideaId);
});

$pendingRequests = computed(function () {
    return IdeaCollaborationRequest::where('idea_id', $this->ideaId)
        ->where('status', 'pending')
        ->with(['collaboratorUser'])
        ->orderBy('created_at', 'desc')
        ->get();
});

$allRequests = computed(function () {
    return IdeaCollaborationRequest::where('idea_id', $this->ideaId)
        ->with(['collaboratorUser'])
        ->orderBy('created_at', 'desc')
        ->get();
});

mount(function (Idea $idea) {
    $this->ideaId = $idea->id;
});

$acceptRequest = function ($requestId) {
    $request = IdeaCollaborationRequest::findOrFail($requestId);

    if ($request->idea_id !== $this->ideaId) {
        abort(403);
    }

    if ($this->idea->user_id !== auth()->id()) {
        abort(403, 'Only the idea owner can accept collaboration requests.');
    }

    try {
        app(CollaborationService::class)->acceptRequest($request, auth()->user());
        session()->flash('success', 'Collaboration request accepted!');
        unset($this->idea);
        unset($this->pendingRequests);
        unset($this->allRequests);
    } catch (\Exception $e) {
        session()->flash('error', 'Failed to accept request: ' . $e->getMessage());
    }
};

$declineRequest = function ($requestId) {
    $validated = $this->validate([
        'responseMessage' => 'nullable|string|max:500',
    ]);

    $request = IdeaCollaborationRequest::findOrFail($requestId);

    if ($request->idea_id !== $this->ideaId) {
        abort(403);
    }

    if ($this->idea->user_id !== auth()->id()) {
        abort(403, 'Only the idea owner can decline collaboration requests.');
    }

    try {
        app(CollaborationService::class)->declineRequest($request, auth()->user(), $validated['responseMessage']);
        $this->reset(['showResponseForm', 'responseMessage', 'respondingToRequest']);
        session()->flash('success', 'Collaboration request declined.');
        unset($this->idea);
        unset($this->pendingRequests);
        unset($this->allRequests);
    } catch (\Exception $e) {
        session()->flash('error', 'Failed to decline request: ' . $e->getMessage());
    }
};

$canManageRequests = computed(function () {
    return auth()->user()->can('manage_collaboration_requests') && $this->idea->user_id === auth()->id();
});

?>

<div>
    @if($this->canManageRequests)
        <!-- Pending Requests -->
        @if($this->pendingRequests->count() > 0)
            <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    Pending Collaboration Requests
                    <span class="ml-2 px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">
                        {{ $this->pendingRequests->count() }}
                    </span>
                </h3>
                <div class="space-y-4">
                    @foreach($this->pendingRequests as $request)
                        <div class="border rounded-lg p-4 hover:bg-gray-50 transition-colors">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-medium">
                                            {{ substr($request->collaboratorUser->name ?? 'U', 0, 1) }}
                                        </div>
                                        <div>
                                            <h4 class="font-medium text-gray-900">{{ $request->collaboratorUser->name ?? 'Unknown User' }}</h4>
                                            <p class="text-sm text-gray-500">
                                                {{ $request->collaboratorUser->email ?? 'No email' }}
                                                <span class="mx-2">•</span>
                                                {{ $request->created_at->diffForHumans() }}
                                            </p>
                                        </div>
                                    </div>

                                    @if($request->request_message)
                                        <div class="mb-3 p-3 bg-blue-50 rounded-lg">
                                            <p class="text-sm text-blue-800">
                                                <strong>Message:</strong> "{{ $request->request_message }}"
                                            </p>
                                        </div>
                                    @endif
                                </div>

                                <div class="flex space-x-2 ml-4">
                                    <flux:button
                                        wire:click="acceptRequest({{ $request->id }})"
                                        variant="primary"
                                        size="sm"
                                    >
                                        Accept
                                    </flux:button>
                                    <flux:button
                                        wire:click="$set('respondingToRequest', {{ $request->id }})"
                                        variant="outline"
                                        size="sm"
                                    >
                                        Decline
                                    </flux:button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- All Requests History -->
        @if($this->allRequests->count() > 0)
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">All Collaboration Requests</h3>
                <div class="space-y-4">
                    @foreach($this->allRequests as $request)
                        <div class="border rounded-lg p-4">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-medium
                                            @if($request->status === 'accepted') bg-green-500
                                            @elseif($request->status === 'declined') bg-red-500
                                            @else bg-yellow-500 @endif">
                                            {{ substr($request->collaboratorUser->name ?? 'U', 0, 1) }}
                                        </div>
                                        <div>
                                            <h4 class="font-medium text-gray-900">{{ $request->collaboratorUser->name ?? 'Unknown User' }}</h4>
                                            <p class="text-sm text-gray-500">
                                                {{ $request->collaboratorUser->email ?? 'No email' }}
                                                <span class="mx-2">•</span>
                                                {{ $request->requested_at ? $request->requested_at->diffForHumans() : $request->created_at->diffForHumans() }}
                                            </p>
                                        </div>
                                    </div>

                                    @if($request->request_message)
                                        <div class="mb-3 p-3 bg-gray-50 rounded-lg">
                                            <p class="text-sm text-gray-700">
                                                <strong>Message:</strong> "{{ $request->request_message }}"
                                            </p>
                                        </div>
                                    @endif

                                    <div class="flex items-center space-x-4 text-sm text-gray-600">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full
                                            @if($request->status === 'accepted') bg-green-100 text-green-800
                                            @elseif($request->status === 'declined') bg-red-100 text-red-800
                                            @else bg-yellow-100 text-yellow-800 @endif">
                                            {{ ucfirst($request->status) }}
                                        </span>
                                        @if($request->response_at)
                                            <span>Responded {{ $request->response_at->diffForHumans() }}</span>
                                        @endif
                                    </div>

                                    @if($request->status !== 'pending' && $request->response_message)
                                        <div class="mt-3 p-3 bg-gray-50 rounded-lg">
                                            <p class="text-sm text-gray-700">
                                                <strong>Response:</strong> "{{ $request->response_message }}"
                                            </p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Empty State -->
        @if($this->allRequests->count() === 0)
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <div class="text-center py-12">
                    <flux:icon name="user-plus" class="w-16 h-16 mx-auto mb-4 text-gray-300" />
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Collaboration Requests</h3>
                    <p class="text-gray-500">
                        When someone requests to collaborate on this idea, it will appear here.
                    </p>
                </div>
            </div>
        @endif
    @else
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="flex">
                <flux:icon name="exclamation-triangle" class="w-5 h-5 text-yellow-400" />
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">Access Restricted</h3>
                    <p class="text-sm text-yellow-700 mt-1">
                        You don't have permission to manage collaboration requests for this idea.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Decline Request Modal -->
    <flux:modal wire:model="respondingToRequest" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Decline Request</flux:heading>
                <flux:subheading>Provide an optional reason for declining this collaboration request</flux:subheading>
            </div>

            <form wire:submit="declineRequest({{ $respondingToRequest }})" class="space-y-6">
                <div class="space-y-2">
                    <flux:label for="responseMessage">Reason (Optional)</flux:label>
                    <flux:textarea
                        wire:model="responseMessage"
                        id="responseMessage"
                        placeholder="Thank you for your interest, but..."
                        rows="3"
                    />
                    <flux:error name="responseMessage" />
                </div>

                <div class="flex justify-end space-x-2">
                    <flux:button
                        wire:click="$set('respondingToRequest', null)"
                        variant="ghost"
                    >
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="outline">
                        Decline Request
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>