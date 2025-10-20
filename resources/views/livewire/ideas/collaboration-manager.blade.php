<?php

use function Livewire\Volt\{state, computed, mount, rules, validate};
use App\Models\Idea;
use App\Models\User;
use App\Services\CollaborationService;
use Illuminate\Validation\Rule;

state([
    'collaborationEnabled' => false,
    'collaborationDeadline' => null,
    'showInviteForm' => false,
    'inviteEmail' => '',
    'inviteMessage' => '',
    'invitePermissions' => 'read',
    'removingCollaborator' => null,
    'updatingPermissions' => null,
    'newPermissions' => 'read',
]);

$idea = computed(function () {
    return Idea::with(['activeCollaborators.user', 'collaborationRequests' => function ($query) {
        $query->pending()->with('invitee');
    }])->findOrFail($this->ideaId);
});

$availablePermissions = [
    'read' => 'Read Only',
    'comment' => 'Read & Comment',
    'edit' => 'Read & Suggest Edits',
    'admin' => 'Full Access',
];

mount(function () {
    $this->collaborationEnabled = $this->idea->collaboration_enabled;
    $this->collaborationDeadline = $this->idea->collaboration_deadline?->format('Y-m-d');
});

$toggleCollaboration = function () {
    $this->authorize('manage_collaboration');

    if ($this->collaborationEnabled) {
        $this->idea->disableCollaboration();
        $this->collaborationEnabled = false;
        session()->flash('success', 'Collaboration disabled for this idea.');
    } else {
        $this->idea->enableCollaboration($this->collaborationDeadline ? \Carbon\Carbon::parse($this->collaborationDeadline) : null);
        $this->collaborationEnabled = true;
        session()->flash('success', 'Collaboration enabled for this idea.');
    }
};

$inviteCollaborator = function () {
    $this->authorize('invite_collaborators');

    $validated = $this->validate([
        'inviteEmail' => 'required|email|exists:users,email',
        'inviteMessage' => 'nullable|string|max:500',
        'invitePermissions' => ['required', Rule::in(array_keys($this->availablePermissions))],
    ]);

    $invitee = User::where('email', $validated['inviteEmail'])->first();

    if (!$invitee) {
        $this->addError('inviteEmail', 'User not found.');
        return;
    }

    if ($this->idea->isCollaborator($invitee)) {
        $this->addError('inviteEmail', 'User is already a collaborator.');
        return;
    }

    try {
        app(CollaborationService::class)->sendInvitation(
            $this->idea,
            $invitee,
            auth()->user(),
            $validated['invitePermissions'],
            $validated['inviteMessage'] ?: null
        );

        $this->reset(['inviteEmail', 'inviteMessage', 'showInviteForm']);
        session()->flash('success', 'Collaboration invitation sent successfully.');
    } catch (\Exception $e) {
        $this->addError('inviteEmail', $e->getMessage());
    }
};

$removeCollaborator = function ($collaboratorId) {
    $this->authorize('manage_collaborators');

    $collaborator = $this->idea->collaborators()->findOrFail($collaboratorId);

    try {
        app(CollaborationService::class)->removeCollaborator(
            $collaborator,
            auth()->user(),
            'Removed by author'
        );

        session()->flash('success', 'Collaborator removed successfully.');
    } catch (\Exception $e) {
        session()->flash('error', 'Failed to remove collaborator: ' . $e->getMessage());
    }
};

$updatePermissions = function ($collaboratorId) {
    $this->authorize('manage_collaborators');

    $validated = $this->validate([
        'newPermissions' => ['required', Rule::in(array_keys($this->availablePermissions))],
    ]);

    $collaborator = $this->idea->collaborators()->findOrFail($collaboratorId);

    try {
        app(CollaborationService::class)->updatePermissions(
            $collaborator,
            $validated['newPermissions'],
            auth()->user()
        );

        $this->reset(['updatingPermissions', 'newPermissions']);
        session()->flash('success', 'Collaborator permissions updated successfully.');
    } catch (\Exception $e) {
        session()->flash('error', 'Failed to update permissions: ' . $e->getMessage());
    }
};

$canManageCollaboration = computed(function () {
    return auth()->user()->can('manage_collaboration') && $this->idea->user_id === auth()->id();
});

$canInviteCollaborators = computed(function () {
    return auth()->user()->can('invite_collaborators') && $this->idea->user_id === auth()->id();
});

$canManageCollaborators = computed(function () {
    return auth()->user()->can('manage_collaborators') && $this->idea->user_id === auth()->id();
});

?>

<div>
    <!-- Collaboration Toggle -->
    @if($canManageCollaboration)
        <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Collaboration Settings</h3>
                    <p class="text-sm text-gray-600 mt-1">
                        {{ $collaborationEnabled ? 'Collaboration is currently enabled' : 'Enable collaboration to allow others to contribute' }}
                    </p>
                </div>
                <div class="flex items-center space-x-4">
                    @if($collaborationEnabled)
                        <div class="text-sm text-gray-500">
                            {{ $idea->activeCollaborators->count() }} collaborator{{ $idea->activeCollaborators->count() !== 1 ? 's' : '' }}
                        </div>
                    @endif
                    <flux:switch
                        wire:model.live="collaborationEnabled"
                        wire:click="toggleCollaboration"
                        :checked="$collaborationEnabled"
                    />
                </div>
            </div>

            @if($collaborationEnabled)
                <div class="mt-4 pt-4 border-t">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <flux:label for="collaborationDeadline">Collaboration Deadline (Optional)</flux:label>
                            <flux:input
                                type="date"
                                wire:model="collaborationDeadline"
                                id="collaborationDeadline"
                                placeholder="No deadline"
                            />
                            <flux:error name="collaborationDeadline" />
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif

    <!-- Collaborators Management -->
    @if($collaborationEnabled)
        <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Collaborators</h3>
                @if($canInviteCollaborators)
                    <flux:button
                        wire:click="$set('showInviteForm', true)"
                        variant="primary"
                        size="sm"
                    >
                        <flux:icon name="user-plus" class="w-4 h-4 mr-2" />
                        Invite Collaborator
                    </flux:button>
                @endif
            </div>

            <!-- Active Collaborators -->
            @if($idea->activeCollaborators->count() > 0)
                <div class="space-y-3">
                    @foreach($idea->activeCollaborators as $collaborator)
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-medium">
                                    {{ substr($collaborator->user->name, 0, 1) }}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">{{ $collaborator->user->name }}</p>
                                    <p class="text-sm text-gray-500">{{ $collaborator->user->email }}</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    @if($collaborator->permissions === 'admin') bg-purple-100 text-purple-800
                                    @elseif($collaborator->permissions === 'edit') bg-blue-100 text-blue-800
                                    @elseif($collaborator->permissions === 'comment') bg-green-100 text-green-800
                                    @else bg-gray-100 text-gray-800 @endif">
                                    {{ $availablePermissions[$collaborator->permissions] ?? $collaborator->permissions }}
                                </span>

                                @if($canManageCollaborators)
                                    <flux:dropdown>
                                        <flux:dropdown.trigger>
                                            <flux:button variant="ghost" size="sm">
                                                <flux:icon name="more-vertical" class="w-4 h-4" />
                                            </flux:button>
                                        </flux:dropdown.trigger>
                                        <flux:dropdown.content>
                                            <flux:dropdown.item wire:click="$set('updatingPermissions', {{ $collaborator->id }})">
                                                Change Permissions
                                            </flux:dropdown.item>
                                            <flux:dropdown.item
                                                wire:click="removeCollaborator({{ $collaborator->id }})"
                                                class="text-red-600"
                                            >
                                                Remove Collaborator
                                            </flux:dropdown.item>
                                        </flux:dropdown.content>
                                    </flux:dropdown>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 text-gray-500">
                    <flux:icon name="users" class="w-12 h-12 mx-auto mb-4 text-gray-300" />
                    <p>No collaborators yet</p>
                    <p class="text-sm">Invite team members to collaborate on this idea</p>
                </div>
            @endif
        </div>

        <!-- Pending Requests -->
        @if($idea->collaborationRequests->count() > 0)
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Pending Invitations</h3>
                <div class="space-y-3">
                    @foreach($idea->collaborationRequests as $request)
                        <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center text-white text-sm font-medium">
                                    {{ substr($request->invitee->name, 0, 1) }}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">{{ $request->invitee->name }}</p>
                                    <p class="text-sm text-gray-500">{{ $request->invitee->email }}</p>
                                    @if($request->message)
                                        <p class="text-sm text-gray-600 mt-1">"{{ Str::limit($request->message, 100) }}"</p>
                                    @endif
                                </div>
                            </div>
                            <div class="text-sm text-gray-500">
                                Invited {{ $request->created_at->diffForHumans() }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif

    <!-- Invite Collaborator Modal -->
    <flux:modal wire:model="showInviteForm" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Invite Collaborator</flux:heading>
                <flux:subheading>Invite someone to collaborate on this idea</flux:subheading>
            </div>

            <form wire:submit="inviteCollaborator" class="space-y-6">
                <div class="space-y-2">
                    <flux:label for="inviteEmail">Email Address</flux:label>
                    <flux:input
                        type="email"
                        wire:model="inviteEmail"
                        id="inviteEmail"
                        placeholder="colleague@kenha.co.ke"
                        required
                    />
                    <flux:error name="inviteEmail" />
                </div>

                <div class="space-y-2">
                    <flux:label for="invitePermissions">Permissions</flux:label>
                    <flux:select wire:model="invitePermissions" id="invitePermissions">
                        @foreach($availablePermissions as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="invitePermissions" />
                </div>

                <div class="space-y-2">
                    <flux:label for="inviteMessage">Personal Message (Optional)</flux:label>
                    <flux:textarea
                        wire:model="inviteMessage"
                        id="inviteMessage"
                        placeholder="I'd like you to help improve this idea..."
                        rows="3"
                    />
                    <flux:error name="inviteMessage" />
                </div>

                <div class="flex justify-end space-x-2">
                    <flux:button
                        wire:click="$set('showInviteForm', false)"
                        variant="ghost"
                    >
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        Send Invitation
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Update Permissions Modal -->
    <flux:modal wire:model="updatingPermissions" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Update Permissions</flux:heading>
                <flux:subheading>Change collaborator access level</flux:subheading>
            </div>

            <form wire:submit="updatePermissions({{ $updatingPermissions }})" class="space-y-6">
                <div class="space-y-2">
                    <flux:label for="newPermissions">Permissions</flux:label>
                    <flux:select wire:model="newPermissions" id="newPermissions">
                        @foreach($availablePermissions as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="newPermissions" />
                </div>

                <div class="flex justify-end space-x-2">
                    <flux:button
                        wire:click="$set('updatingPermissions', null)"
                        variant="ghost"
                    >
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        Update Permissions
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>