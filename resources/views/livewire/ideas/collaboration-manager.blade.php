<?php

use function Livewire\Volt\{state, computed, mount};
use App\Models\Idea;
use App\Models\User;
use App\Services\CollaborationService;
use Illuminate\Validation\Rule;

state([
    'ideaId' => null,
    'collaborationEnabled' => false,
    'collaborationDeadline' => null,
    'showInviteForm' => false,
    'inviteEmail' => '',
    'inviteMessage' => '',
    'invitePermissions' => 'suggest',
    'removingCollaborator' => null,
    'updatingPermissions' => null,
    'newPermissions' => 'suggest',
]);

// Computed property for idea with relationships
$idea = computed(function () {
    return Idea::with(['activeCollaborators.user', 'collaborationRequests' => function ($query) {
        $query->where('status', 'pending')->with('requester');
    }])->findOrFail($this->ideaId);
});

// Helper function for available permissions
$getAvailablePermissions = function () {
    return [
        'suggest' => 'Can Suggest Edits',
        'edit' => 'Can Edit Directly',
    ];
};

mount(function (Idea $idea) {
    $this->ideaId = $idea->id;
    $this->collaborationEnabled = $idea->collaboration_enabled;
    $this->collaborationDeadline = $idea->collaboration_deadline?->format('Y-m-d');
});

$toggleCollaboration = function () {
    $idea = Idea::findOrFail($this->ideaId);
    $this->authorize('manageCollaboration', $idea);

    if ($this->collaborationEnabled) {
        $idea->update(['collaboration_enabled' => false]);
        $this->collaborationEnabled = false;
        session()->flash('success', 'Collaboration disabled for this idea.');
    } else {
        $idea->update([
            'collaboration_enabled' => true,
            'collaboration_deadline' => $this->collaborationDeadline ? \Carbon\Carbon::parse($this->collaborationDeadline) : null,
        ]);
        $this->collaborationEnabled = true;
        session()->flash('success', 'Collaboration enabled for this idea.');
    }

    // Refresh the idea
    unset($this->idea);
};

$inviteCollaborator = function () use ($getAvailablePermissions) {
    $idea = Idea::findOrFail($this->ideaId);
    $this->authorize('inviteCollaborators', $idea);

    $availablePermissions = $getAvailablePermissions();
    
    $validated = $this->validate([
        'inviteEmail' => 'required|email|exists:users,email',
        'inviteMessage' => 'nullable|string|max:500',
        'invitePermissions' => ['required', Rule::in(array_keys($availablePermissions))],
    ]);

    $invitee = User::where('email', $validated['inviteEmail'])->first();

    if (!$invitee) {
        $this->addError('inviteEmail', 'User not found.');
        return;
    }

    $idea = Idea::findOrFail($this->ideaId);

    // Check if user is already a collaborator
    if ($idea->activeCollaborators()->where('user_id', $invitee->id)->exists()) {
        $this->addError('inviteEmail', 'User is already a collaborator.');
        return;
    }

    try {
        app(CollaborationService::class)->sendInvitation(
            $idea,
            $invitee,
            auth()->user(),
            $validated['invitePermissions'],
            $validated['inviteMessage'] ?: null
        );

        $this->reset(['inviteEmail', 'inviteMessage', 'showInviteForm']);
        session()->flash('success', 'Collaboration invitation sent successfully.');
        
        // Refresh the idea
        unset($this->idea);
    } catch (\Exception $e) {
        $this->addError('inviteEmail', $e->getMessage());
    }
};

$removeCollaborator = function ($collaboratorId) {
    $idea = Idea::findOrFail($this->ideaId);
    $this->authorize('manageCollaborators', $idea);

    $collaborator = $idea->activeCollaborators()->findOrFail($collaboratorId);

    try {
        app(CollaborationService::class)->removeCollaborator(
            $collaborator,
            auth()->user(),
            'Removed by author'
        );

        session()->flash('success', 'Collaborator removed successfully.');
        
        // Refresh the idea
        unset($this->idea);
    } catch (\Exception $e) {
        session()->flash('error', 'Failed to remove collaborator: ' . $e->getMessage());
    }
};

$updatePermissions = function ($collaboratorId) use ($getAvailablePermissions) {
    $idea = Idea::findOrFail($this->ideaId);
    $this->authorize('manageCollaborators', $idea);

    $availablePermissions = $getAvailablePermissions();
    
    $validated = $this->validate([
        'newPermissions' => ['required', Rule::in(array_keys($availablePermissions))],
    ]);

    $idea = Idea::findOrFail($this->ideaId);
    $collaborator = $idea->activeCollaborators()->findOrFail($collaboratorId);

    try {
        app(CollaborationService::class)->updatePermissions(
            $collaborator,
            $validated['newPermissions'],
            auth()->user()
        );

        $this->reset(['updatingPermissions', 'newPermissions']);
        session()->flash('success', 'Collaborator permissions updated successfully.');
        
        // Refresh the idea
        unset($this->idea);
    } catch (\Exception $e) {
        session()->flash('error', 'Failed to update permissions: ' . $e->getMessage());
    }
};

?>

<div>
    @php
        $availablePermissions = [
            'suggest' => 'Can Suggest Edits',
            'edit' => 'Can Edit Directly',
        ];
    @endphp

    <!-- Collaboration Toggle -->
    @if(auth()->user() && $this->idea && auth()->user()->can('manageCollaboration', $this->idea))
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 p-6 mb-6">
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
                            {{ $this->idea->activeCollaborators->count() }} collaborator{{ $this->idea->activeCollaborators->count() !== 1 ? 's' : '' }}
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
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-[#231F20] dark:text-white">Collaborators</h3>
                @if(auth()->user() && $this->idea && auth()->user()->can('inviteCollaborators', $this->idea))
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
            @if($this->idea->activeCollaborators->count() > 0)
                <div class="space-y-3">
                    @foreach($this->idea->activeCollaborators as $collaborator)
                        <div class="flex items-center justify-between p-3 bg-[#F8EBD5]/40 dark:bg-zinc-900/40 rounded-xl">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-gradient-to-br from-[#FFF200] via-yellow-300 to-yellow-400 dark:from-yellow-400 dark:via-yellow-500 dark:to-yellow-600 rounded-full flex items-center justify-center text-[#231F20] dark:text-zinc-900 text-sm font-medium shadow">
                                    {{ substr($collaborator->user->name, 0, 1) }}
                                </div>
                                <div>
                                    <p class="font-medium text-[#231F20] dark:text-white">{{ $collaborator->user->name }}</p>
                                    <p class="text-sm text-[#9B9EA4] dark:text-zinc-400">{{ $collaborator->user->email }}</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    @if($collaborator->permission_level === 'edit') bg-blue-100 text-blue-800
                                    @else bg-green-100 text-green-800 @endif">
                                    {{ $availablePermissions[$collaborator->permission_level] ?? $collaborator->permission_level }}
                                </span>

                                @if(auth()->user()->can('manageCollaborators', $this->idea))
                                    <flux:dropdown>
                                        <flux:button variant="ghost" size="sm">
                                            <flux:icon name="ellipsis-vertical" class="w-4 h-4" />
                                        </flux:button>
                                        <flux:menu>
                                            <flux:menu.item wire:click="$set('updatingPermissions', {{ $collaborator->id }})">
                                                Change Permissions
                                            </flux:menu.item>
                                            <flux:menu.item
                                                wire:click="removeCollaborator({{ $collaborator->id }})"
                                                variant="danger"
                                            >
                                                Remove Collaborator
                                            </flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 text-[#9B9EA4] dark:text-zinc-400">
                    <flux:icon name="users" class="w-12 h-12 mx-auto mb-4 text-gray-300" />
                    <p>No collaborators yet</p>
                    <p class="text-sm">Invite team members to collaborate on this idea</p>
                </div>
            @endif
        </div>

        <!-- Pending Requests -->
        @if($this->idea->collaborationRequests->count() > 0)
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Pending Invitations</h3>
                <div class="space-y-3">
                    @foreach($this->idea->collaborationRequests as $request)
                        <div class="flex items-center justify-between p-3 bg-yellow-50 dark:bg-yellow-900/30 rounded-xl">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-gradient-to-br from-[#FFF200] via-yellow-300 to-yellow-400 dark:from-yellow-400 dark:via-yellow-500 dark:to-yellow-600 rounded-full flex items-center justify-center text-[#231F20] dark:text-zinc-900 text-sm font-medium shadow">
                                    {{ substr($request->requester->name, 0, 1) }}
                                </div>
                                <div>
                                    <p class="font-medium text-[#231F20] dark:text-white">{{ $request->requester->name }}</p>
                                    <p class="text-sm text-[#9B9EA4] dark:text-zinc-400">{{ $request->requester->email }}</p>
                                    @if($request->request_message)
                                        <p class="text-sm text-gray-600 mt-1">"{{ Str::limit($request->request_message, 100) }}"</p>
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