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
}; ?>

<div class="space-y-6">
    {{-- Header --}}
    <div class="border-b border-[#E6E8EB] dark:border-zinc-700 pb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-[#231F20] dark:text-white">
                    Public Ideas for Collaboration
                </h1>
                <p class="mt-2 text-[#9B9EA4] dark:text-zinc-400">
                    Discover innovative ideas from the KeNHA community that are open for collaboration and contribution.
                </p>
            </div>
            <div class="text-right">
                <div class="text-2xl font-bold text-[#2563EB]">
                    {{ $this->getIdeas()->total() }}
                </div>
                <div class="text-sm text-[#9B9EA4] dark:text-zinc-400">
                    Ideas Available
                </div>
            </div>
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
    <div class="flex items-center justify-between">
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
                    Date {{ $sortField === 'created_at' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' }}
                </button>
                <button
                    wire:click="sortBy('idea_title')"
                    class="text-sm px-3 py-1 rounded-md transition-colors
                        {{ $sortField === 'idea_title'
                            ? 'bg-[#2563EB] text-white'
                            : 'text-[#9B9EA4] dark:text-zinc-400 hover:text-[#231F20] dark:hover:text-white hover:bg-gray-50 dark:hover:bg-zinc-900' }}"
                >
                    Title {{ $sortField === 'idea_title' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' }}
                </button>
                <button
                    wire:click="sortBy('comments_count')"
                    class="text-sm px-3 py-1 rounded-md transition-colors
                        {{ $sortField === 'comments_count'
                            ? 'bg-[#2563EB] text-white'
                            : 'text-[#9B9EA4] dark:text-zinc-400 hover:text-[#231F20] dark:hover:text-white hover:bg-gray-50 dark:hover:bg-zinc-900' }}"
                >
                    Comments {{ $sortField === 'comments_count' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' }}
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
        :columns="'grid-cols-1 md:grid-cols-2 lg:grid-cols-3'"
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
                    :subtitle="'By ' . ($idea->user->staff?->first_name ? $idea->user->staff->first_name . ' ' . ($idea->user->staff->other_names ?? '') : $idea->user->email)"
                    :badge="[
                        'text' => $this->getStatusText($idea->status),
                        'variant' => $this->getStatusBadgeVariant($idea->status)
                    ]"
                    :meta="[
                        ['icon' => 'calendar', 'text' => $idea->created_at->format('M j, Y')],
                        ['icon' => 'chat-bubble-left-ellipsis', 'text' => $idea->comments_count . ' comments'],
                        ['icon' => 'users', 'text' => 'Open for collaboration']
                    ]"
                />

                <x-cards.card-body>
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

                <x-cards.card-footer
                    :meta="[
                        ['icon' => 'eye', 'text' => 'Click to view details'],
                        ['icon' => 'users', 'text' => 'Open for collaboration']
                    ]"
                    :actions="[
                        [
                            'text' => 'View Details',
                            'variant' => 'primary',
                            'wireClick' => 'viewIdea(\'' . $idea->slug . '\')'
                        ],
                        [
                            'text' => 'Learn More',
                            'variant' => 'ghost',
                            'href' => route('ideas.public.show', ['idea' => $idea->slug])
                        ]
                    ]"
                />
            </x-cards.card>
        @endforeach
    </x-cards.card-grid>

    {{-- Pagination --}}
    <x-cards.pagination
        :paginator="$this->getIdeas()"
        :per-page-options="[12, 24, 48]"
    />
</div>