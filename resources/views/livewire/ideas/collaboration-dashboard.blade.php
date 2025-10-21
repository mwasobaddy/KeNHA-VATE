<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\Idea;
use App\Models\IdeaCollaborator;
use App\Models\IdeaCollaborationRequest;

new #[Layout('components.layouts.app')] class extends Component {
    public $activeTab = 'overview';

    // Sync tab state with URL query string
    public array $queryString = [
        'activeTab' => ['except' => 'overview'],
    ];

    /**
     * Get user's collaborative ideas
     */
    public function getCollaborativeIdeas()
    {
        $user = Auth::user();
        if (!$user) {
            return collect();
        }

        return $user->ideas()
            ->where('collaboration_enabled', true)
            ->with(['collaborators', 'collaborationRequests'])
            ->withCount(['collaborators', 'revisions'])
            ->get();
    }

    /**
     * Get ideas where user is a collaborator
     */
    public function getCollaborationParticipations()
    {
        $userId = Auth::id();
        if (!$userId) {
            return collect();
        }

        return IdeaCollaborator::where('user_id', $userId)
            ->with(['idea.user', 'idea.collaborators'])
            ->get()
            ->map(function ($collaborator) {
                return $collaborator->idea;
            });
    }

    /**
     * Get pending collaboration requests
     */
    public function getPendingRequests()
    {
        $userId = Auth::id();
        if (!$userId) {
            return collect();
        }

        return IdeaCollaborationRequest::where('requester_id', $userId)
            ->where('status', 'pending')
            ->with(['idea.user'])
            ->get();
    }

    /**
     * Get collaboration requests to review
     */
    public function getRequestsToReview()
    {
        $user = Auth::user();
        if (!$user) {
            return collect();
        }

        $userIdeas = $user->ideas()->pluck('id');
        return IdeaCollaborationRequest::whereIn('idea_id', $userIdeas)
            ->where('status', 'pending')
            ->with(['requester', 'idea'])
            ->get();
    }

    /**
     * Set the active tab
     */
    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    /**
     * Get recent collaboration activity
     */
    public function getRecentActivity()
    {
        $user = Auth::user();
        if (!$user) {
            return collect();
        }

        $activities = collect();

        // Recent revisions on user's ideas
        $revisionActivity = $user->ideas()
            ->with(['revisions' => function ($query) {
                $query->latest()->take(5);
            }])
            ->get()
            ->pluck('revisions')
            ->flatten()
            ->sortByDesc('created_at')
            ->take(10);

        // Recent collaboration requests
        $requestActivity = IdeaCollaborationRequest::where(function ($query) use ($user) {
            $query->where('requester_id', $user->id)
                  ->orWhereIn('idea_id', $user->ideas()->pluck('id'));
        })
        ->with(['requester', 'idea'])
        ->latest()
        ->take(10)
        ->get();

        return collect([...$revisionActivity, ...$requestActivity])
            ->sortByDesc(function ($item) {
                return $item->created_at ?? $item->updated_at;
            })
            ->take(15);
    }

    /**
     * Approve a collaboration request
     */
    public function approveRequest($requestId)
    {
        $request = IdeaCollaborationRequest::findOrFail($requestId);
        $user = Auth::user();

        // Verify the user owns the idea
        if ($request->idea->user_id !== $user->id) {
            session()->flash('error', 'You can only approve requests for your own ideas.');
            return;
        }

        try {
            app(\App\Services\CollaborationService::class)->acceptRequest($request, $user);
            session()->flash('success', 'Collaboration request approved successfully!');
            
            // Refresh the data
            unset($this->getRequestsToReview);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to approve request: ' . $e->getMessage());
        }
    }

    /**
     * Reject a collaboration request
     */
    public function rejectRequest($requestId)
    {
        $request = IdeaCollaborationRequest::findOrFail($requestId);
        $user = Auth::user();

        // Verify the user owns the idea
        if ($request->idea->user_id !== $user->id) {
            session()->flash('error', 'You can only reject requests for your own ideas.');
            return;
        }

        try {
            app(\App\Services\CollaborationService::class)->declineRequest($request, $user, 'Request declined by author');
            session()->flash('success', 'Collaboration request rejected.');
            
            // Refresh the data
            unset($this->getRequestsToReview);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to reject request: ' . $e->getMessage());
        }
    }
};
?>

<div class="backdrop-blur-lg">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 border border-zinc-200 dark:border-yellow-400 rounded-3xl bg-gradient-to-br from-[#F8EBD5]/20 via-white to-[#F8EBD5] dark:from-zinc-900/20 dark:via-zinc-800 dark:to-zinc-900 border">

        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-[#231F20] dark:text-white">
                        {{ __('Collaboration Dashboard') }}
                    </h1>
                    <p class="mt-2 text-[#9B9EA4] dark:text-zinc-400">
                        {{ __('Manage your collaborative ideas and collaboration requests') }}
                    </p>
                </div>
                <flux:button
                    icon="arrow-left"
                    wire:navigate
                    href="{{ route('ideas.table') }}"
                    variant="primary"
                    class="bg-[#FFF200] hover:bg-yellow-400 text-[#231F20] dark:bg-yellow-500 dark:hover:bg-yellow-600"
                >
                    {{ __('Back to Ideas') }}
                </flux:button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-xl bg-blue-100 dark:bg-blue-900/20">
                        <flux:icon name="light-bulb" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-[#9B9EA4] dark:text-zinc-400">{{ __('My Collaborative Ideas') }}</p>
                        <p class="text-2xl font-bold text-[#231F20] dark:text-white">{{ $this->getCollaborativeIdeas()->count() }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-xl bg-green-100 dark:bg-green-900/20">
                        <flux:icon name="users" class="w-6 h-6 text-green-600 dark:text-green-400" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-[#9B9EA4] dark:text-zinc-400">{{ __('Active Collaborations') }}</p>
                        <p class="text-2xl font-bold text-[#231F20] dark:text-white">{{ $this->getCollaborationParticipations()->count() }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-xl bg-yellow-100 dark:bg-yellow-900/20">
                        <flux:icon name="clock" class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-[#9B9EA4] dark:text-zinc-400">{{ __('Pending Requests') }}</p>
                        <p class="text-2xl font-bold text-[#231F20] dark:text-white">{{ $this->getPendingRequests()->count() }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-xl bg-purple-100 dark:bg-purple-900/20">
                        <flux:icon name="bell" class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-[#9B9EA4] dark:text-zinc-400">{{ __('Requests to Review') }}</p>
                        <p class="text-2xl font-bold text-[#231F20] dark:text-white">{{ $this->getRequestsToReview()->count() }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Tabs -->
        <div x-data="{ activeTab: $wire.activeTab }" class="border border-zinc-200 dark:border-yellow-400 rounded-3xl bg-gradient-to-br from-[#F8EBD5]/20 via-white to-[#F8EBD5] dark:from-zinc-900/20 dark:via-zinc-800 dark:to-zinc-900 border">
            <div class="border-b border-zinc-200 dark:border-yellow-400">
                <nav class="flex">
                    <button
                        @click="activeTab = 'overview'"
                        wire:click="setActiveTab('overview')"
                        :class="activeTab === 'overview' ? 'border-[#FFF200] text-[#231F20] dark:text-white' : 'border-transparent text-[#9B9EA4] dark:text-zinc-400 hover:text-[#231F20] dark:hover:text-white'"
                        class="flex-1 py-4 px-6 border-b-2 font-medium text-sm text-center transition-colors duration-200"
                    >
                        {{ __('Overview') }}
                    </button>
                    <button
                        @click="activeTab = 'my-ideas'"
                        wire:click="setActiveTab('my-ideas')"
                        :class="activeTab === 'my-ideas' ? 'border-[#FFF200] text-[#231F20] dark:text-white' : 'border-transparent text-[#9B9EA4] dark:text-zinc-400 hover:text-[#231F20] dark:hover:text-white'"
                        class="flex-1 py-4 px-6 border-b-2 font-medium text-sm text-center transition-colors duration-200"
                    >
                        {{ __('My Collaborative Ideas') }}
                    </button>
                    <button
                        @click="activeTab = 'participating'"
                        wire:click="setActiveTab('participating')"
                        :class="activeTab === 'participating' ? 'border-[#FFF200] text-[#231F20] dark:text-white' : 'border-transparent text-[#9B9EA4] dark:text-zinc-400 hover:text-[#231F20] dark:hover:text-white'"
                        class="flex-1 py-4 px-6 border-b-2 font-medium text-sm text-center transition-colors duration-200"
                    >
                        {{ __('Participating In') }}
                    </button>
                    <button
                        @click="activeTab = 'requests'"
                        wire:click="setActiveTab('requests')"
                        :class="activeTab === 'requests' ? 'border-[#FFF200] text-[#231F20] dark:text-white' : 'border-transparent text-[#9B9EA4] dark:text-zinc-400 hover:text-[#231F20] dark:hover:text-white'"
                        class="flex-1 py-4 px-6 border-b-2 font-medium text-sm text-center transition-colors duration-200"
                    >
                        {{ __('Requests') }}
                    </button>
                </nav>
            </div>

            <div class="p-6">
                <!-- Overview Tab -->
                <div x-show="activeTab === 'overview'" x-transition>
                    <div class="space-y-6">
                        <div>
                            <h3 class="text-lg font-semibold text-[#231F20] dark:text-white mb-4">{{ __('Recent Activity') }}</h3>
                            <div class="space-y-4">
                                @forelse($this->getRecentActivity() as $activity)
                                    <div class="flex items-start space-x-3 p-4 bg-[#F8EBD5]/20 dark:bg-zinc-700/20 rounded-lg">
                                        <div class="flex-shrink-0">
                                            @if(isset($activity->revision_type))
                                                <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/20 flex items-center justify-center">
                                                    <flux:icon name="document-text" class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                                                </div>
                                            @else
                                                <div class="w-8 h-8 rounded-full bg-purple-100 dark:bg-purple-900/20 flex items-center justify-center">
                                                    <flux:icon name="user-plus" class="w-4 h-4 text-purple-600 dark:text-purple-400" />
                                                </div>
                                            @endif
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm text-[#231F20] dark:text-white">
                                                @if(isset($activity->revision_type))
                                                    {{ __('Revision') }} <x-revision-badge :revision="$activity" /> {{ __('on') }}
                                                    <a href="{{ route('ideas.show', $activity->idea->slug) }}" class="font-medium text-[#231F20] dark:text-white hover:text-[#FFF200] dark:hover:text-yellow-400">
                                                        {{ $activity->idea->idea_title }}
                                                    </a>
                                                @else
                                                    {{ __('Collaboration request') }}
                                                    <span class="font-medium">{{ $activity->status === 'pending' ? 'sent' : $activity->status }}</span>
                                                    {{ __('for') }}
                                                    <a href="{{ route('ideas.show', $activity->idea->slug) }}" class="font-medium text-[#231F20] dark:text-white hover:text-[#FFF200] dark:hover:text-yellow-400">
                                                        {{ $activity->idea->idea_title }}
                                                    </a>
                                                @endif
                                            </p>
                                            <p class="text-xs text-[#9B9EA4] dark:text-zinc-400">
                                                {{ $activity->created_at->diffForHumans() }}
                                            </p>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center py-8">
                                        <flux:icon name="document" class="w-12 h-12 text-[#9B9EA4] dark:text-zinc-400 mx-auto mb-4" />
                                        <p class="text-[#9B9EA4] dark:text-zinc-400">{{ __('No recent collaboration activity') }}</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <!-- My Collaborative Ideas Tab -->
                <div x-show="activeTab === 'my-ideas'" x-transition>
                    <div class="space-y-4">
                        @forelse($this->getCollaborativeIdeas() as $idea)
                            <div class="border border-[#9B9EA4]/20 dark:border-zinc-700 rounded-lg p-4 hover:bg-[#F8EBD5]/10 dark:hover:bg-zinc-700/10 transition-colors">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <h4 class="font-semibold text-[#231F20] dark:text-white">
                                                <a href="{{ route('ideas.show', $idea->slug) }}" class="hover:text-[#FFF200] dark:hover:text-yellow-400">
                                                    {{ $idea->idea_title }}
                                                </a>
                                            </h4>
                                            <x-collaboration-status :idea="$idea" />
                                        </div>
                                        <p class="text-sm text-[#9B9EA4] dark:text-zinc-400 mb-3">{{ Str::limit($idea->abstract, 150) }}</p>
                                        <div class="flex items-center gap-4 text-xs text-[#9B9EA4] dark:text-zinc-400">
                                            <span>{{ $idea->collaborators_count }} {{ __('collaborator') }}{{ $idea->collaborators_count !== 1 ? 's' : '' }}</span>
                                            <span>{{ $idea->revisions_count }} {{ __('revision') }}{{ $idea->revisions_count !== 1 ? 's' : '' }}</span>
                                            <span>{{ $idea->collaborationRequests->where('status', 'pending')->count() }} {{ __('pending request') }}{{ $idea->collaborationRequests->where('status', 'pending')->count() !== 1 ? 's' : '' }}</span>
                                        </div>
                                    </div>
                                    <flux:button
                                        icon="eye"
                                        wire:navigate
                                        href="{{ route('ideas.show', $idea->slug) }}"
                                        variant="ghost"
                                        size="sm"
                                    >
                                        {{ __('View') }}
                                    </flux:button>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-12">
                                <flux:icon name="light-bulb" class="w-16 h-16 text-[#9B9EA4] dark:text-zinc-400 mx-auto mb-4" />
                                <h3 class="text-lg font-semibold text-[#231F20] dark:text-white mb-2">{{ __('No Collaborative Ideas') }}</h3>
                                <p class="text-[#9B9EA4] dark:text-zinc-400 mb-4">{{ __('You haven\'t enabled collaboration on any of your ideas yet.') }}</p>
                                <flux:button
                                    icon="plus"
                                    wire:navigate
                                    href="{{ route('ideas.table') }}"
                                    variant="primary"
                                    class="bg-[#FFF200] hover:bg-yellow-400 text-[#231F20] dark:bg-yellow-500 dark:hover:bg-yellow-600"
                                >
                                    {{ __('Create Collaborative Idea') }}
                                </flux:button>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Participating In Tab -->
                <div x-show="activeTab === 'participating'" x-transition>
                    <div class="space-y-4">
                        @forelse($this->getCollaborationParticipations() as $idea)
                            <div class="border border-[#9B9EA4]/20 dark:border-zinc-700 rounded-lg p-4 hover:bg-[#F8EBD5]/10 dark:hover:bg-zinc-700/10 transition-colors">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <h4 class="font-semibold text-[#231F20] dark:text-white">
                                                <a href="{{ route('ideas.show', $idea->slug) }}" class="hover:text-[#FFF200] dark:hover:text-yellow-400">
                                                    {{ $idea->idea_title }}
                                                </a>
                                            </h4>
                                            <x-collaboration-status :idea="$idea" />
                                        </div>
                                        <p class="text-sm text-[#9B9EA4] dark:text-zinc-400 mb-2">
                                            {{ __('by') }} {{ $idea->user->email }}
                                        </p>
                                        <p class="text-sm text-[#9B9EA4] dark:text-zinc-400">{{ Str::limit($idea->abstract, 150) }}</p>
                                    </div>
                                    <flux:button
                                        icon="eye"
                                        wire:navigate
                                        href="{{ route('ideas.show', $idea->slug) }}"
                                        variant="ghost"
                                        size="sm"
                                    >
                                        {{ __('View') }}
                                    </flux:button>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-12">
                                <flux:icon name="users" class="w-16 h-16 text-[#9B9EA4] dark:text-zinc-400 mx-auto mb-4" />
                                <h3 class="text-lg font-semibold text-[#231F20] dark:text-white mb-2">{{ __('Not Participating') }}</h3>
                                <p class="text-[#9B9EA4] dark:text-zinc-400 mb-4">{{ __('You\'re not collaborating on any ideas yet.') }}</p>
                                <flux:button
                                    icon="magnifying-glass"
                                    wire:navigate
                                    href="{{ route('ideas.public') }}"
                                    variant="primary"
                                    class="bg-[#FFF200] hover:bg-yellow-400 text-[#231F20] dark:bg-yellow-500 dark:hover:bg-yellow-600"
                                >
                                    {{ __('Find Collaborative Ideas') }}
                                </flux:button>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Requests Tab -->
                <div x-show="activeTab === 'requests'" x-transition>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- My Requests -->
                        <div>
                            <h3 class="text-lg font-semibold text-[#231F20] dark:text-white mb-4">{{ __('My Requests') }}</h3>
                            <div class="space-y-3">
                                @forelse($this->getPendingRequests() as $request)
                                    <div class="border border-[#9B9EA4]/20 dark:border-zinc-700 rounded-lg p-4">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">
                                                <p class="font-medium text-[#231F20] dark:text-white mb-1">
                                                    {{ $request->idea->idea_title }}
                                                </p>
                                                <p class="text-sm text-[#9B9EA4] dark:text-zinc-400">
                                                    {{ __('Requested') }} {{ $request->created_at->diffForHumans() }}
                                                </p>
                                            </div>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-400">
                                                {{ __('Pending') }}
                                            </span>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center py-8">
                                        <flux:icon name="clock" class="w-8 h-8 text-[#9B9EA4] dark:text-zinc-400 mx-auto mb-2" />
                                        <p class="text-sm text-[#9B9EA4] dark:text-zinc-400">{{ __('No pending requests') }}</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <!-- Requests to Review -->
                        <div>
                            <h3 class="text-lg font-semibold text-[#231F20] dark:text-white mb-4">{{ __('Requests to Review') }}</h3>
                            <div class="space-y-3">
                                @forelse($this->getRequestsToReview() as $request)
                                    <div class="border border-[#9B9EA4]/20 dark:border-zinc-700 rounded-lg p-4">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">
                                                <p class="font-medium text-[#231F20] dark:text-white mb-1">
                                                    {{ $request->requester->email }}
                                                </p>
                                                <p class="text-sm text-[#9B9EA4] dark:text-zinc-400 mb-2">
                                                    {{ __('wants to collaborate on') }} {{ $request->idea->idea_title }}
                                                </p>
                                                <p class="text-xs text-[#9B9EA4] dark:text-zinc-400">
                                                    {{ $request->created_at->diffForHumans() }}
                                                </p>
                                            </div>
                                            <div class="flex gap-2">
                                                <flux:button
                                                    icon="check-badge"
                                                    wire:click="approveRequest({{ $request->id }})"
                                                    variant="primary"
                                                    size="sm"
                                                    color="green"
                                                >
                                                    {{ __('Approve') }}
                                                </flux:button>
                                                <flux:button
                                                    icon="trash"
                                                    wire:click="rejectRequest({{ $request->id }})"
                                                    variant="primary"
                                                    size="sm"
                                                    color="red"
                                                >
                                                    {{ __('Reject') }}
                                                </flux:button>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center py-8">
                                        <flux:icon name="bell" class="w-8 h-8 text-[#9B9EA4] dark:text-zinc-400 mx-auto mb-2" />
                                        <p class="text-sm text-[#9B9EA4] dark:text-zinc-400">{{ __('No requests to review') }}</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <style>
        /* Custom Scrollbar for better UX */
        ::-webkit-scrollbar {
            width: 5px;
        }

        ::-webkit-scrollbar-track {
            background: #F8EBD5;
        }

        ::-webkit-scrollbar-thumb {
            background: #FFF200;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #F4E000;
        }

        .dark ::-webkit-scrollbar-track {
            background: #374151;
        }

        .dark ::-webkit-scrollbar-thumb {
            background: #F59E0B;
        }

        .dark ::-webkit-scrollbar-thumb:hover {
            background: #D97706;
        }

        /* Smooth transitions for all interactive elements */
        * {
            transition: all 0.2s ease-in-out;
        }

        /* Input focus enhancement */
        input:focus, select:focus {
            box-shadow: 0 0 0 3px rgba(255, 242, 0, 0.1);
        }
    </style>
</div>