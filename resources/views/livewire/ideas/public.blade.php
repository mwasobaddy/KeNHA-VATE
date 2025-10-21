<?php

use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Idea;
use Illuminate\Database\Eloquent\Builder;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    // Search and filtering
    public string $search = '';
    public string $thematicArea = '';

    // Updating state trackers
    public bool $updatingSearch = false;
    public bool $updatingThematicArea = false;

    // Sorting
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';

    // Pagination
    public int $perPage = 12;

    // Collaboration modal properties
    public ?int $selectedIdeaId = null;
    public bool $showCollaborationModal = false;
    public string $collaborationMessage = '';
    public string $proposedContribution = '';
    public string $requesterExperience = '';
    public bool $acceptTerms = false;

    /**
     * Sync filter state to the URL query string for persistence and shareability
     */
    public array $queryString = [
        'search' => ['except' => ''],
        'thematicArea' => ['except' => ''],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
        'perPage' => ['except' => 12],
    ];

    /**
     * Get the public ideas available for collaboration
     */
    public function getIdeas()
    {
        $query = Idea::where('collaboration_enabled', true)
            ->where('status', 'submitted') // Only show submitted ideas
            ->with(['thematicArea', 'user.staff'])
            ->withCount(['comments' => function (Builder $query) {
                $query->where('comment_is_disabled', false);
            }])
            ->when($this->search, function (Builder $query) {
                $query->where(function (Builder $q) {
                    $q->where('idea_title', 'like', '%' . $this->search . '%')
                      ->orWhere('abstract', 'like', '%' . $this->search . '%')
                      ->orWhere('problem_statement', 'like', '%' . $this->search . '%')
                      ->orWhere('proposed_solution', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->thematicArea, function (Builder $query) {
                $query->where('thematic_area_id', $this->thematicArea);
            })
            ->orderBy($this->sortField, $this->sortDirection);

        return $query->paginate($this->perPage);
    }

    /**
     * Get available thematic areas for filtering
     */
    public function getThematicAreas()
    {
        return \App\Models\ThematicArea::where('is_active', true)
            ->whereHas('ideas', function (Builder $query) {
                $query->where('collaboration_enabled', true)
                      ->where('status', 'submitted');
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Sort the cards by a specific field
     */
    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    /**
     * View idea details
     */
    public function viewIdea(string $slug): void
    {
        $this->redirect(route('ideas.public.show', ['idea' => $slug]), navigate: true);
    }

    /**
     * View idea comments
     */
    public function viewComments(string $slug): void
    {
        $this->redirect(route('ideas.public.show', ['idea' => $slug]) . '#comments', navigate: true);
    }

    /**
     * Get status badge variant
     */
    public function getStatusBadgeVariant(string $status): string
    {
        return match($status) {
            'draft' => 'warning',
            'submitted' => 'success',
            'under_review' => 'primary',
            'approved' => 'success',
            'rejected' => 'danger',
            default => 'default'
        };
    }

    /**
     * Get status display text
     */
    public function getStatusText(string $status): string
    {
        return match($status) {
            'draft' => 'Draft',
            'submitted' => 'Submitted',
            'under_review' => 'Under Review',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => 'Unknown'
        };
    }

    /**
     * Toggle like for an idea
     */
    public function toggleLike(int $ideaId): void
    {
        // Ensure user is authenticated
        if (!auth()->check()) {
            session()->flash('error', 'Please login to like ideas.');
            return;
        }

        $idea = Idea::findOrFail($ideaId);
        $userId = auth()->id();

        $existingLike = $idea->likes()->where('user_id', $userId)->first();

        if ($existingLike) {
            // Unlike: remove the like
            $existingLike->delete();
        } else {
            // Like: create new like
            $idea->likes()->create(['user_id' => $userId]);
        }

        // No need to update likedIdeas array since isLiked() checks database directly
    }

    /**
     * Check if an idea is liked by current user
     */
    public function isLiked(int $ideaId): bool
    {
        if (!auth()->check()) {
            return false;
        }

        return \App\Models\IdeaLike::where('user_id', auth()->id())
            ->where('idea_id', $ideaId)
            ->exists();
    }

    /**
     * Reset all filters
     */
    public function resetFilters(): void
    {
        $this->search = '';
        $this->thematicArea = '';
        $this->sortField = 'created_at';
        $this->sortDirection = 'desc';
        $this->perPage = 12;
    }

    /**
     * Open collaboration request modal
     */
    public function openCollaborationModal(int $ideaId): void
    {
        if (!auth()->check()) {
            session()->flash('error', 'Please login to request collaboration.');
            return;
        }

        $this->selectedIdeaId = $ideaId;
        $this->collaborationMessage = '';
        $this->proposedContribution = '';
        $this->requesterExperience = '';
        $this->acceptTerms = false;
        $this->showCollaborationModal = true;
    }

    /**
     * Close collaboration request modal
     */
    public function closeCollaborationModal(): void
    {
        $this->showCollaborationModal = false;
        $this->selectedIdeaId = null;
        $this->collaborationMessage = '';
        $this->proposedContribution = '';
        $this->requesterExperience = '';
        $this->acceptTerms = false;
    }

    /**
     * Submit collaboration request
     */
    public function submitCollaborationRequest(): void
    {
        if (!auth()->check()) {
            session()->flash('error', 'Please login to request collaboration.');
            return;
        }

        $this->validate([
            'collaborationMessage' => 'required|string|min:50|max:1000',
            'proposedContribution' => 'required|string|min:20|max:500',
            'requesterExperience' => 'required|string|min:20|max:500',
            'acceptTerms' => 'accepted',
        ], [
            'collaborationMessage.required' => 'Please explain why you want to collaborate on this idea.',
            'collaborationMessage.min' => 'Your message must be at least 50 characters.',
            'proposedContribution.required' => 'Please describe what you can contribute.',
            'requesterExperience.required' => 'Please share your relevant experience.',
            'acceptTerms.accepted' => 'You must accept the collaboration terms.',
        ]);

        try {
            $idea = Idea::findOrFail($this->selectedIdeaId);
            $user = auth()->user();

            $collaborationService = app(\App\Services\CollaborationService::class);
            $request = $collaborationService->submitCollaborationRequest(
                $idea,
                $user,
                $this->collaborationMessage,
                $this->proposedContribution,
                $this->requesterExperience
            );

            session()->flash('success', 'Your collaboration request has been submitted successfully! The idea author will review your request.');
            $this->closeCollaborationModal();

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to submit collaboration request: ' . $e->getMessage());
        }
    }
}; ?>


<div class="backdrop-blur-lg">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 border border-zinc-200 dark:border-yellow-400 rounded-3xl bg-gradient-to-br from-[#F8EBD5]/20 via-white to-[#F8EBD5] dark:from-zinc-900/20 dark:via-zinc-800 dark:to-zinc-900 border">
    {{-- Header --}}
            <!-- Header Section -->
        <div class="mb-8 sm:mb-12">
            <div class="flex flex-row items-start sm:items-center gap-4 sm:gap-6">
                <!-- Animated Icon Badge -->
                <div 
                    x-data="{ show: false }" 
                    x-init="setTimeout(() => show = true, 100)"
                    x-show="show"
                    x-transition:enter="transition ease-out duration-500 delay-100"
                    x-transition:enter-start="opacity-0 scale-75 -rotate-12"
                    x-transition:enter-end="opacity-100 scale-100 rotate-0"
                    class="flex-shrink-0"
                >
                    <div class="relative">
                        <div class="absolute inset-0 bg-[#FFF200]/20 dark:bg-yellow-400/20 rounded-2xl blur-xl"></div>
                        <div class="relative flex items-center justify-center w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-gradient-to-br from-[#FFF200] via-yellow-300 to-yellow-400 dark:from-yellow-400 dark:via-yellow-500 dark:to-yellow-600 shadow-lg">
                            <flux:icon name="users" class="w-8 h-8 sm:w-10 sm:h-10 text-[#231F20] dark:text-zinc-900" />
                        </div>
                    </div>
                </div>

                <!-- Header Text with staggered animation -->
                <div 
                    class="flex-1"
                    x-data="{ show: false }" 
                    x-init="setTimeout(() => show = true, 200)"
                >
                    <div 
                        x-show="show"
                        x-transition:enter="transition ease-out duration-700"
                        x-transition:enter-start="opacity-0 translate-x-4"
                        x-transition:enter-end="opacity-100 translate-x-0"
                    >
                        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-[#231F20] dark:text-white tracking-tight">
                            Public Ideas for Collaboration
                        </h1>
                        <p class="mt-2 text-base sm:text-lg text-[#9B9EA4] dark:text-zinc-400">
                            Discover innovative ideas from the KeNHA community that are open for collaboration and contribution.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Public Ideas Stats Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 p-6">
                <h2 class="text-sm font-medium text-[#9B9EA4] dark:text-zinc-400">Total Collaborative Ideas</h2>
                <p class="mt-1 text-3xl font-semibold text-[#231F20] dark:text-white">{{ \App\Models\Idea::where('collaboration_enabled', true)->where('status', 'submitted')->count() }}</p>
            </div>
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 p-6">
                <h2 class="text-sm font-medium text-[#9B9EA4] dark:text-zinc-400">Active Thematic Areas</h2>
                <p class="mt-1 text-3xl font-semibold text-[#231F20] dark:text-white">{{ $this->getThematicAreas()->count() }}</p>
            </div>
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 p-6">
                <h2 class="text-sm font-medium text-[#9B9EA4] dark:text-zinc-400">Total Contributors</h2>
                <p class="mt-1 text-3xl font-semibold text-[#231F20] dark:text-white">{{ \App\Models\Idea::where('collaboration_enabled', true)->where('status', 'submitted')->distinct('user_id')->count() }}</p>
            </div>
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 p-6">
                <h2 class="text-sm font-medium text-[#9B9EA4] dark:text-zinc-400">Total Comments</h2>
                <p class="mt-1 text-3xl font-semibold text-[#231F20] dark:text-white">{{ \App\Models\Comment::whereHas('idea', function($query) { $query->where('collaboration_enabled', true)->where('status', 'submitted'); })->where('comment_is_disabled', false)->count() }}</p>
            </div>
        </div>

    {{-- Filters --}}
    <x-cards.filters
        :search="$search"
        searchPlaceholder="Search ideas by title, abstract, or content..."
        :perPage="$perPage"
    >
        <x-slot name="filters">
            <flux:select
                wire:model.live="thematicArea"
                class="border border-[#9B9EA4]/20 dark:border-zinc-700 rounded-md px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-[#231F20] dark:text-white focus:ring-2 focus:ring-[#FFF200] focus:border-[#FFF200] !w-fit"
            >
                <flux:select.option value="">All Thematic Areas</flux:select.option>
                @foreach($this->getThematicAreas() as $area)
                    <flux:select.option value="{{ $area->id }}">{{ $area->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:button
                icon="x-mark"
                wire:click="resetFilters"
                variant="primary"
                color="gray"
                size="sm"
            >
                {{ __('Reset Filters') }}
            </flux:button>
        </x-slot>
    </x-cards.filters>

    {{-- Sort Options --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center space-x-4">
            <span class="text-sm font-medium text-[#231F20] dark:text-white">Sort by:</span>
            <div class="flex items-center space-x-2">
                <button
                    wire:click="sortBy('created_at')"
                    class="text-sm px-3 py-1 rounded-md transition-colors
                        {{ $sortField === 'created_at'
                            ? 'bg-[#2563EB] text-white'
                            : 'text-[#9B9EA4] dark:text-zinc-400 hover:text-[#231F20] dark:hover:text-white hover:bg-gray-50 dark:hover:bg-zinc-900' }}"
                >
                    Date {{ $sortField === 'created_at' ? ($sortDirection === 'asc' ? '↑' : '↓') : '↑↓' }}
                </button>
                <button
                    wire:click="sortBy('idea_title')"
                    class="text-sm px-3 py-1 rounded-md transition-colors
                        {{ $sortField === 'idea_title'
                            ? 'bg-[#2563EB] text-white'
                            : 'text-[#9B9EA4] dark:text-zinc-400 hover:text-[#231F20] dark:hover:text-white hover:bg-gray-50 dark:hover:bg-zinc-900' }}"
                >
                    Title {{ $sortField === 'idea_title' ? ($sortDirection === 'asc' ? '↑' : '↓') : '↑↓' }}
                </button>
                <button
                    wire:click="sortBy('comments_count')"
                    class="text-sm px-3 py-1 rounded-md transition-colors
                        {{ $sortField === 'comments_count'
                            ? 'bg-[#2563EB] text-white'
                            : 'text-[#9B9EA4] dark:text-zinc-400 hover:text-[#231F20] dark:hover:text-white hover:bg-gray-50 dark:hover:bg-zinc-900' }}"
                >
                    Comments {{ $sortField === 'comments_count' ? ($sortDirection === 'asc' ? '↑' : '↓') : '↑↓' }}
                </button>
            </div>
        </div>

        <div class="text-sm text-[#9B9EA4] dark:text-zinc-400">
            Showing {{ $this->getIdeas()->firstItem() ?? 0 }} to {{ $this->getIdeas()->lastItem() ?? 0 }}
            of {{ $this->getIdeas()->total() }} ideas
        </div>
    </div>

    {{-- Ideas Cards Grid --}}
    <x-cards.card-grid
        :loading="false"
        :empty="!$this->getIdeas()->count()"
        empty-title="No collaborative ideas found"
        empty-description="There are currently no ideas available for collaboration. Check back later or submit your own idea!"
        :columns="'grid-cols-1 md:grid-cols-2'"
    >
        @foreach($this->getIdeas() as $idea)
            <x-cards.card
                :wire:key="'idea-' . $idea->id"
                hoverable="{{ true }}"
                clickable="{{ true }}"
                wire:click="viewIdea('{{ $idea->slug }}')"
                class="cursor-pointer"
            >
                <x-cards.card-header
                    :title="$idea->idea_title"
                    :subtitle="'By ' . ($idea->user->first_name ? $idea->user->first_name . ' ' . ($idea->user->other_names ?? '') : $idea->user->email)"
                    :badge="[
                        'text' => $this->getStatusText($idea->status),
                        'variant' => $this->getStatusBadgeVariant($idea->status)
                    ]"
                    :meta="[
                        ['icon' => 'calendar', 'text' => $idea->created_at->format('M j, Y')],
                        ['icon' => 'chat-bubble-left-right', 'text' => $idea->comments_count . ' comments'],
                        ['icon' => 'users', 'text' => 'Open for collaboration']
                    ]"
                />

                <x-cards.card-body class="flex-1">
                    <div class="space-y-3">
                        @if($idea->thematicArea)
                            <div class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#FFF200] text-[#231F20]">
                                <flux:icon name="tag" class="h-3 w-3 mr-1" />
                                {{ $idea->thematicArea->name }}
                            </div>
                        @endif

                        @if($idea->abstract)
                            <p class="text-sm text-[#231F20] dark:text-zinc-200 line-clamp-3">
                                {{ Str::limit($idea->abstract, 150) }}
                            </p>
                        @endif

                        @if($idea->team_effort && $idea->team_members)
                            <div class="flex items-center text-xs text-[#9B9EA4] dark:text-zinc-400">
                                <flux:icon name="users" class="h-3 w-3 mr-1" />
                                Team effort with {{ count($idea->team_members) }} members
                            </div>
                        @endif
                    </div>
                </x-cards.card-body>

                <!-- Custom Footer with Like Functionality -->
                <div class="px-6 py-4 border-t border-gray-200 dark:border-zinc-700">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-4">
                            <!-- Like Button -->
                            @if(auth()->check())
                                <button
                                    wire:click="toggleLike({{ $idea->id }})"
                                    class="flex items-center space-x-1 text-sm transition-colors duration-200 hover:scale-105
                                        {{ $this->isLiked($idea->id)
                                            ? 'text-red-600'
                                            : 'text-red-600' }}"
                                >
                                    <flux:icon
                                        name="heart"
                                        class="h-4 w-4 {{ $this->isLiked($idea->id) ? 'fill-current' : '' }}"
                                    />
                                    <span>{{ $idea->likes_count }}</span>
                                </button>
                            @else
                                <button
                                    onclick="window.location.href='{{ route('login') }}'"
                                    class="flex items-center space-x-1 text-sm transition-colors duration-200 hover:scale-105 text-gray-400 hover:text-gray-500 dark:text-zinc-500 dark:hover:text-zinc-400 cursor-pointer"
                                    title="Login to like this idea"
                                >
                                    <flux:icon name="heart" class="h-4 w-4" />
                                    <span>{{ $idea->likes_count }}</span>
                                </button>
                            @endif

                            <!-- Comments -->
                            <div class="flex items-center space-x-1 text-sm text-gray-500 dark:text-zinc-400">
                                <flux:icon name="chat-bubble-left-right" class="h-4 w-4" />
                                <span>{{ $idea->comments_count }}</span>
                            </div>
                        </div>

                        <div class="flex items-center space-x-2">
                            <flux:button
                                icon="chat-bubble-left-right"
                                size="sm"
                                variant="primary"
                                wire:click="viewComments('{{ $idea->slug }}')"
                                title="View comments for this idea"
                                class="bg-[#FFF200] hover:bg-[#FFF200]/90 dark:bg-yellow-400 dark:hover:bg-yellow-300 text-[#231F20] dark:text-zinc-900 transition-all duration-200"
                            >
                                Comments
                            </flux:button>
                            <flux:button
                                icon="eye"
                                size="sm"
                                variant="primary"
                                color="blue"
                                wire:click="viewIdea('{{ $idea->slug }}')"
                            >
                                View Details
                            </flux:button>
                        </div>
                    </div>
                    <div class="w-full">
                        {{-- if idea belongs to user logged in disable button --}}
                        @if($idea->user_id === Auth::id())
                            <flux:button
                                icon="user-plus"
                                size="sm"
                                variant="primary"
                                title="You cannot collaborate on your own idea"
                                class="w-full cursor-not-allowed bg-gray-300 hover:bg-gray-400 text-gray-600 dark:bg-zinc-700 hover:bg-zinc-600 dark:text-zinc-400"
                            >
                                Collaborate
                            </flux:button>
                        @else
                            <flux:button
                                icon="user-plus"
                                size="sm"
                                variant="primary"
                                color="green"
                                wire:click="openCollaborationModal({{ $idea->id }})"
                                title="Request to collaborate on this idea"
                                class="w-full"
                            >
                                Collaborate
                            </flux:button>
                        @endif
                    </div>
                </div>
            </x-cards.card>
        @endforeach
    </x-cards.card-grid>

    {{-- Pagination --}}
    <x-cards.pagination
        :paginator="$this->getIdeas()"
        :per-page-options="[12, 24, 48]"
    />

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

    {{-- Collaboration Request Modal --}}
    <x-modal
        model="showCollaborationModal"
        title="Request Collaboration"
        submit="submitCollaborationRequest"
        submit-text="Submit Request"
        cancel="closeCollaborationModal"
    >
        <div class="space-y-6">
            @if($selectedIdeaId)
                @php
                    $selectedIdea = \App\Models\Idea::find($selectedIdeaId);
                @endphp

                @if($selectedIdea)
                    <!-- Idea Preview -->
                    <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-4 border border-gray-200 dark:border-zinc-700">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">{{ $selectedIdea->idea_title }}</h4>
                        <p class="text-sm text-gray-600 dark:text-zinc-400 line-clamp-2">
                            {{ Str::limit($selectedIdea->abstract, 150) }}
                        </p>
                        <div class="flex items-center mt-2 text-xs text-gray-500 dark:text-zinc-500">
                            <span>By {{ $selectedIdea->user->first_name ?? $selectedIdea->user->email }}</span>
                        </div>
                    </div>
                @endif
            @endif

            <!-- Collaboration Message -->
            <div>
                <flux:input
                    wire:model="collaborationMessage"
                    :label="__('Why do you want to collaborate on this idea?')"
                    type="textarea"
                    rows="4"
                    required
                    placeholder="Explain your interest in this idea and why you'd be a good collaborator..."
                    class="transition-all duration-200 focus:ring-2 focus:ring-[#FFF200] dark:focus:ring-yellow-400"
                />
                <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">
                    Minimum 50 characters. Be specific about your motivation and goals.
                </p>
            </div>

            <!-- Proposed Contribution -->
            <div>
                <flux:input
                    wire:model="proposedContribution"
                    :label="__('What can you contribute to this idea?')"
                    type="textarea"
                    rows="3"
                    required
                    placeholder="Describe your skills, expertise, or resources you can bring to the collaboration..."
                    class="transition-all duration-200 focus:ring-2 focus:ring-[#FFF200] dark:focus:ring-yellow-400"
                />
                <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">
                    Minimum 20 characters. Focus on what value you can add.
                </p>
            </div>

            <!-- Experience -->
            <div>
                <flux:input
                    wire:model="requesterExperience"
                    :label="__('Relevant Experience & Background')"
                    type="textarea"
                    rows="3"
                    required
                    placeholder="Share your relevant experience, qualifications, or background that makes you suitable for this collaboration..."
                    class="transition-all duration-200 focus:ring-2 focus:ring-[#FFF200] dark:focus:ring-yellow-400"
                />
                <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">
                    Minimum 20 characters. Help the author understand your capabilities.
                </p>
            </div>

            <!-- Terms Acceptance -->
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                <div class="flex items-start space-x-3">
                    <flux:checkbox
                        wire:model="acceptTerms"
                        class="mt-0.5"
                    />
                    <div class="flex-1">
                        <h4 class="text-sm font-medium text-yellow-800 dark:text-yellow-200 mb-1">
                            Collaboration Terms & IP Agreement
                        </h4>
                        <div class="text-xs text-yellow-700 dark:text-yellow-300 space-y-1">
                            <p>By requesting collaboration, you agree to:</p>
                            <ul class="list-disc list-inside space-y-0.5 ml-2">
                                <li>Respect intellectual property rights and confidentiality</li>
                                <li>Contribute constructively to the idea development</li>
                                <li>Follow the collaboration guidelines set by the idea author</li>
                                <li>Not share or disclose idea details without permission</li>
                                <li>Any collaborative work may be subject to IP agreements</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Validation Errors -->
            @error('collaborationMessage')
                <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
            @error('proposedContribution')
                <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
            @error('requesterExperience')
                <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
            @error('acceptTerms')
                <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>
    </x-modal>

</div>