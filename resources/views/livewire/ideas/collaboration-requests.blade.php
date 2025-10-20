<?php

use function Livewire\Volt\{state, computed, mount};
use App\Models\IdeaCollaborationRequest;
use App\Services\CollaborationService;

state([
    'showResponseForm' => false,
    'responseMessage' => '',
    'respondingToRequest' => null,
]);

$pendingRequests = computed(function () {
    return IdeaCollaborationRequest::where('invitee_id', auth()->id())
        ->where('status', 'pending')
        ->with(['idea.user', 'inviter'])
        ->orderBy('created_at', 'desc')
        ->get();
});

$sentRequests = computed(function () {
    return IdeaCollaborationRequest::where('inviter_id', auth()->id())
        ->with(['idea.user', 'invitee'])
        ->orderBy('created_at', 'desc')
        ->get();
});

$acceptRequest = function ($requestId) {
    $request = IdeaCollaborationRequest::findOrFail($requestId);

    // Ensure user can respond to this request
    if ($request->invitee_id !== auth()->id()) {
        abort(403);
    }

    try {
        app(CollaborationService::class)->acceptInvitation($request, auth()->user());
        session()->flash('success', 'Collaboration invitation accepted! You are now a collaborator on this idea.');
    } catch (\Exception $e) {
        session()->flash('error', 'Failed to accept invitation: ' . $e->getMessage());
    }
};

$declineRequest = function ($requestId) {
    $validated = $this->validate([
        'responseMessage' => 'nullable|string|max:500',
    ]);

    $request = IdeaCollaborationRequest::findOrFail($requestId);

    // Ensure user can respond to this request
    if ($request->invitee_id !== auth()->id()) {
        abort(403);
    }

    try {
        app(CollaborationService::class)->declineInvitation($request, auth()->user(), $validated['responseMessage']);
        $this->reset(['showResponseForm', 'responseMessage', 'respondingToRequest']);
        session()->flash('success', 'Collaboration invitation declined.');
    } catch (\Exception $e) {
        session()->flash('error', 'Failed to decline invitation: ' . $e->getMessage());
    }
};

$canRespondToRequests = computed(function () {
    return auth()->user()->can('respond_to_collaboration_requests');
});

$canViewCollaborationRequests = computed(function () {
    return auth()->user()->can('view_collaboration_requests');
});

?>

<div>
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Collaboration Requests</h1>
                <p class="text-gray-600 mt-1">Manage collaboration invitations and requests</p>
            </div>
            <div class="flex items-center space-x-4">
                <div class="text-sm text-gray-500">
                    {{ $pendingRequests->count() }} pending invitation{{ $pendingRequests->count() !== 1 ? 's' : '' }}
                </div>
            </div>
        </div>
    </div>

    @if(!$canViewCollaborationRequests)
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="flex">
                <flux:icon name="exclamation-triangle" class="w-5 h-5 text-yellow-400" />
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">Access Restricted</h3>
                    <p class="text-sm text-yellow-700 mt-1">
                        You don't have permission to view collaboration requests.
                    </p>
                </div>
            </div>
        </div>
    @else
        <!-- Pending Invitations -->
        @if($pendingRequests->count() > 0)
            <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Pending Invitations</h2>
                <div class="space-y-4">
                    @foreach($pendingRequests as $request)
                        <div class="border rounded-lg p-4 hover:bg-gray-50 transition-colors">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-medium">
                                            {{ substr($request->idea->idea_title, 0, 1) }}
                                        </div>
                                        <div>
                                            <h3 class="font-medium text-gray-900">{{ $request->idea->idea_title }}</h3>
                                            <p class="text-sm text-gray-500">
                                                Invited by {{ $request->inviter->name }}
                                                <span class="mx-2">•</span>
                                                {{ $request->created_at->diffForHumans() }}
                                            </p>
                                        </div>
                                    </div>

                                    @if($request->message)
                                        <div class="mb-3 p-3 bg-blue-50 rounded-lg">
                                            <p class="text-sm text-blue-800">
                                                <strong>Message:</strong> "{{ $request->message }}"
                                            </p>
                                        </div>
                                    @endif

                                    <div class="flex items-center space-x-4 text-sm text-gray-600">
                                        <span>Author: {{ $request->idea->user->name }}</span>
                                        <span>•</span>
                                        <span>Permissions: {{ ucfirst($request->permissions) }}</span>
                                    </div>
                                </div>

                                @if($canRespondToRequests)
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
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Sent Requests -->
        @if($sentRequests->count() > 0)
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Sent Invitations</h2>
                <div class="space-y-4">
                    @foreach($sentRequests as $request)
                        <div class="border rounded-lg p-4">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center text-white font-medium">
                                            {{ substr($request->idea->idea_title, 0, 1) }}
                                        </div>
                                        <div>
                                            <h3 class="font-medium text-gray-900">{{ $request->idea->idea_title }}</h3>
                                            <p class="text-sm text-gray-500">
                                                Sent to {{ $request->invitee->name }}
                                                <span class="mx-2">•</span>
                                                {{ $request->created_at->diffForHumans() }}
                                            </p>
                                        </div>
                                    </div>

                                    @if($request->message)
                                        <div class="mb-3 p-3 bg-gray-50 rounded-lg">
                                            <p class="text-sm text-gray-700">
                                                <strong>Your message:</strong> "{{ $request->message }}"
                                            </p>
                                        </div>
                                    @endif

                                    <div class="flex items-center space-x-4 text-sm text-gray-600">
                                        <span>Permissions: {{ ucfirst($request->permissions) }}</span>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full
                                            @if($request->status === 'accepted') bg-green-100 text-green-800
                                            @elseif($request->status === 'declined') bg-red-100 text-red-800
                                            @else bg-yellow-100 text-yellow-800 @endif">
                                            {{ ucfirst($request->status) }}
                                        </span>
                                    </div>

                                    @if($request->status !== 'pending' && $request->response_message)
                                        <div class="mt-3 p-3 bg-gray-50 rounded-lg">
                                            <p class="text-sm text-gray-700">
                                                <strong>Response:</strong> "{{ $request->response_message }}"
                                            </p>
                                        </div>
                                    @endif
                                </div>

                                <div class="text-sm text-gray-500 ml-4">
                                    @if($request->status === 'pending')
                                        Waiting for response
                                    @elseif($request->status === 'accepted')
                                        Accepted {{ $request->updated_at->diffForHumans() }}
                                    @elseif($request->status === 'declined')
                                        Declined {{ $request->updated_at->diffForHumans() }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Empty State -->
        @if($pendingRequests->count() === 0 && $sentRequests->count() === 0)
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <div class="text-center py-12">
                    <flux:icon name="user-plus" class="w-16 h-16 mx-auto mb-4 text-gray-300" />
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Collaboration Requests</h3>
                    <p class="text-gray-500 mb-6">
                        When someone invites you to collaborate on an idea or you send an invitation,
                        it will appear here.
                    </p>
                    <flux:button
                        href="{{ route('ideas.index') }}"
                        variant="primary"
                    >
                        Browse Ideas
                    </flux:button>
                </div>
            </div>
        @endif
    @endif

    <!-- Decline Request Modal -->
    <flux:modal wire:model="respondingToRequest" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Decline Invitation</flux:heading>
                <flux:subheading>Provide an optional reason for declining this collaboration invitation</flux:subheading>
            </div>

            <form wire:submit="declineRequest({{ $respondingToRequest }})" class="space-y-6">
                <div class="space-y-2">
                    <flux:label for="responseMessage">Reason (Optional)</flux:label>
                    <flux:textarea
                        wire:model="responseMessage"
                        id="responseMessage"
                        placeholder="I'm not available to collaborate at this time..."
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
                        Decline Invitation
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>