<?php

use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Idea;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;
    // Search and filtering
    public string $search = '';
    public string $status = '';
    public string $thematicArea = '';

    // Updating state trackers
    public bool $updatingSearch = false;
    public bool $updatingStatus = false;
    public bool $updatingThematicArea = false;

    // Sorting
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';

    // Pagination
    public int $perPage = 10;


    // Bulk actions
    public array $selectedIdeas = [];
    public bool $selectAll = false;

    // Delete modal
    public bool $showDeleteModal = false;
    public ?int $deleteIdeaId = null;
    public array $deleteSelectedIds = [];

    /**
     * Sync filter state to the URL query string for persistence and shareability
     */
    public array $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'thematicArea' => ['except' => ''],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
        'perPage' => ['except' => 10],
    ];

    /**
     * Get the ideas for the current user
     */
    public function getIdeas()
    {
        $query = Idea::where('user_id', Auth::id())
            ->with(['thematicArea'])
            ->withCount('comments')
            ->when($this->search, function (Builder $query) {
                $query->where(function (Builder $q) {
                    $q->where('idea_title', 'like', '%' . $this->search . '%')
                      ->orWhere('abstract', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->status, function (Builder $query) {
                $query->where('status', $this->status);
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
            ->orderBy('name')
            ->get();
    }

    /**
     * Sort the table by a specific field
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
     * Toggle select all ideas
     */
    public function updatedSelectAll(): void
    {
        if ($this->selectAll) {
            $this->selectedIdeas = $this->getIdeas()->pluck('id')->toArray();
        } else {
            $this->selectedIdeas = [];
        }
    }

    /**
     * Clear all selections
     */
    public function clearSelection(): void
    {
        $this->selectedIdeas = [];
        $this->selectAll = false;
    }

    /**
     * View idea details
     */
    public function viewIdea(int $ideaId): void
    {
        $idea = Idea::where('user_id', Auth::id())->findOrFail($ideaId);

        // Ensure the idea has a slug
        if (!$idea->slug) {
            $base = \Illuminate\Support\Str::slug(substr($idea->idea_title ?: 'idea', 0, 50));
            if (!$base) {
                $base = 'idea';
            }
            $slug = $base;
            $counter = 1;
            while (Idea::where('slug', $slug)->exists()) {
                $slug = $base . '-' . $counter++;
            }
            $idea->update(['slug' => $slug]);
            $idea->refresh();
        }

        // Debug: Log the route and slug
        \Log::info('Viewing idea', [
            'id' => $idea->id,
            'slug' => $idea->slug,
            'status' => $idea->status,
            'route' => $idea->status === 'draft' 
                ? route('ideas.edit_draft.draft', ['draft' => $idea->slug])
                : route('ideas.show', ['idea' => $idea->slug])
        ]);

        // For now, redirect to edit if draft, or show read-only view
        if ($idea->status === 'draft') {
            $this->redirect(route('ideas.edit_draft.draft', ['draft' => $idea->slug]), navigate: true);
        } else {
            $this->redirect(route('ideas.show', ['idea' => $idea->slug]), navigate: true);
        }
    }

    /**
     * Edit an idea (only if not in final review)
     */
    public function editIdea(int $ideaId)
    {
        $idea = Idea::where('user_id', Auth::id())->findOrFail($ideaId);

        // Allow editing if draft or submitted (assuming no final review status yet)
        if (in_array($idea->status, ['draft', 'submitted'])) {
            if ($idea->status === 'draft') {
                return redirect()->route('ideas.edit_draft.draft', ['draft' => $idea->slug]);
            } else {
                // For submitted ideas, convert back to draft for editing
                $idea->update(['status' => 'draft']);
                return redirect()->route('ideas.edit_draft.draft', ['draft' => $idea->slug]);
            }
        }

        session()->flash('error', 'This idea cannot be edited as it has reached the final review stage.');
    }

    /**
     * Open delete modal for single idea
     */
    public function openDeleteModal(int $ideaId): void
    {
        $this->deleteIdeaId = $ideaId;
        $this->deleteSelectedIds = [];
        $this->showDeleteModal = true;
    }

    /**
     * Open delete modal for selected ideas
     */
    public function openDeleteSelectedModal(): void
    {
        $this->deleteSelectedIds = $this->selectedIdeas;
        $this->deleteIdeaId = null;
        $this->showDeleteModal = true;
    }

    /**
     * Close delete modal
     */
    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deleteIdeaId = null;
        $this->deleteSelectedIds = [];
    }

        /**
     * Soft delete single idea
     */
    public function softDeleteIdea(): void
    {
        if ($this->deleteIdeaId) {
            $idea = Idea::where('user_id', Auth::id())->findOrFail($this->deleteIdeaId);

            // Soft delete is allowed for non-draft ideas (submitted, in_review, etc.)
            if ($idea->status !== 'draft') {
                $idea->delete(); // Soft delete
                session()->flash('success', 'Idea moved to trash successfully.');
            } else {
                session()->flash('error', 'Draft ideas should be permanently deleted.');
            }
        }

        $this->closeDeleteModal();
        $this->clearSelection();
    }

    /**
     * Permanently delete single idea
     */
    public function permanentDeleteIdea(): void
    {
        if ($this->deleteIdeaId) {
            $idea = Idea::where('user_id', Auth::id())->withTrashed()->findOrFail($this->deleteIdeaId);

            // Permanent delete is allowed for draft ideas
            if ($idea->status === 'draft') {
                $idea->forceDelete(); // Permanent delete
                session()->flash('success', 'Idea permanently deleted successfully.');
            } else {
                session()->flash('error', 'Only draft ideas can be permanently deleted.');
            }
        }

        $this->closeDeleteModal();
        $this->clearSelection();
    }

    /**
     * Soft delete selected ideas
     */
    public function softDeleteSelected(): void
    {
        $ideas = Idea::where('user_id', Auth::id())
            ->whereIn('id', $this->deleteSelectedIds)
            ->where('status', '!=', 'draft') // Only non-draft ideas can be soft deleted
            ->get();

        if ($ideas->count() > 0) {
            foreach ($ideas as $idea) {
                $idea->delete(); // Soft delete
            }
            session()->flash('success', $ideas->count() . ' idea(s) moved to trash successfully.');
        } else {
            session()->flash('error', 'No eligible ideas were selected for soft deletion. Only non-draft ideas can be moved to trash.');
        }

        $this->closeDeleteModal();
        $this->clearSelection();
    }

    /**
     * Permanently delete selected ideas
     */
    public function permanentDeleteSelected(): void
    {
        $ideas = Idea::where('user_id', Auth::id())
            ->withTrashed()
            ->whereIn('id', $this->deleteSelectedIds)
            ->where('status', 'draft') // Only draft ideas can be permanently deleted
            ->get();

        if ($ideas->count() > 0) {
            foreach ($ideas as $idea) {
                $idea->forceDelete(); // Permanent delete
            }
            session()->flash('success', $ideas->count() . ' draft idea(s) permanently deleted successfully.');
        } else {
            session()->flash('error', 'No draft ideas were selected for permanent deletion.');
        }

        $this->closeDeleteModal();
        $this->clearSelection();
    }

    /**
     * Restore a soft-deleted idea (if soft deletes are enabled)
     */
    public function restoreIdea(int $ideaId): void
    {
        $idea = Idea::withTrashed()->where('user_id', Auth::id())->findOrFail($ideaId);

        if ($idea->trashed()) {
            $idea->restore();
            session()->flash('success', 'Idea restored successfully.');
        } else {
            session()->flash('info', 'Idea is not deleted.');
        }
    }

    /**
     * Restore selected soft-deleted ideas
     */
    public function restoreSelected(): void
    {
        $ideas = Idea::withTrashed()
            ->where('user_id', Auth::id())
            ->whereIn('id', $this->selectedIdeas)
            ->get();

        $restored = 0;
        foreach ($ideas as $idea) {
            if ($idea->trashed()) {
                $idea->restore();
                $restored++;
            }
        }

        if ($restored > 0) {
            session()->flash('success', "$restored idea(s) restored successfully.");
        } else {
            session()->flash('info', 'No deleted ideas were selected for restore.');
        }

        $this->clearSelection();
    }

    /**
     * Export selected ideas
     */
    public function exportSelected(): void
    {
        // TODO: Implement export functionality
        session()->flash('info', 'Export functionality will be implemented soon.');
    }

    /**
     * Reset filters
     */
    public function resetFilters(): void
    {
        $this->search = '';
        $this->status = '';
        $this->thematicArea = '';
        $this->resetPage();
    }

    /**
     * Handle search updates
     */
    public function updatingSearch(): void
    {
        $this->updatingSearch = true;
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->updatingSearch = false;
    }

    /**
     * Handle status updates
     */
    public function updatingStatus(): void
    {
        $this->updatingStatus = true;
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->updatingStatus = false;
    }

    /**
     * Handle thematic area updates
     */
    public function updatingThematicArea(): void
    {
        $this->updatingThematicArea = true;
        $this->resetPage();
    }

    public function updatedThematicArea(): void
    {
        $this->updatingThematicArea = false;
    }
};
?>

<div class="backdrop-blur-lg min-h-screen bg-gradient-to-br from-[#F8EBD5]/20 via-white to-[#F8EBD5] dark:from-zinc-900/20 dark:via-zinc-800 dark:to-zinc-900 border border-zinc-200 dark:border-yellow-400 rounded-3xl py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto space-y-8">

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
                            <flux:icon name="light-bulb" class="w-8 h-8 sm:w-10 sm:h-10 text-[#231F20] dark:text-zinc-900" />
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
                            {{ __('My Ideas') }}
                        </h1>
                        <p class="mt-2 text-base sm:text-lg text-[#9B9EA4] dark:text-zinc-400">
                            {{ __('View and manage all your submitted innovation ideas') }}
                        </p>
                    </div>
                </div>
            </div>
            
            {{-- add a section with add idea button and bulk export button --}}
            <div class="hidden sm:flex justify-end items-center mb-4 gap-4 flex-1">
                <flux:button
                    icon="arrow-path"
                    wire:click="$refresh"
                    variant="primary"
                    class="bg-green-600 hover:bg-green-400 text-[#231F20] dark:bg-green-500 dark:hover:bg-green-600"
                >
                    <span>{{ __('Refresh') }}</span>
                </flux:button>

                <flux:button
                    icon="arrow-down-tray"
                    wire:click=""
                    variant="primary"
                    class="bg-blue-600 hover:bg-blue-700 text-white dark:bg-blue-500 dark:hover:bg-blue-600"
                >
                    <span>{{ __('Export All') }}</span>
                </flux:button>
                
                <flux:button
                    icon="plus"
                    wire:click=""
                    variant="primary"
                    class="bg-[#FFF200] hover:bg-yellow-400 text-[#231F20] dark:bg-yellow-500 dark:hover:bg-yellow-600"
                >
                    <span>{{ __('Add Idea') }}</span>
                </flux:button>
            </div>

            {{-- add a section with add idea button and bulk export button --}}
            <div class="flex sm:hidden justify-end items-center mb-4 gap-4 flex-1">
                <flux:button
                    icon="arrow-path"
                    wire:click="$refresh"
                    variant="primary"
                    class="bg-green-600 hover:bg-green-400 text-[#231F20] dark:bg-green-500 dark:hover:bg-green-600"
                >
                </flux:button>

                <flux:button
                    icon="arrow-down-tray"
                    wire:click=""
                    variant="primary"
                    class="bg-blue-600 hover:bg-blue-700 text-white dark:bg-blue-500 dark:hover:bg-blue-600"
                >
                </flux:button>
                
                <flux:button
                    icon="plus"
                    wire:click=""
                    variant="primary"
                    class="bg-[#FFF200] hover:bg-yellow-400 text-[#231F20] dark:bg-yellow-500 dark:hover:bg-yellow-600"
                >
                </flux:button>
            </div>
        </div>

        {{-- 4 card to display idea stats --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 p-6">
                <h2 class="text-sm font-medium text-[#9B9EA4] dark:text-zinc-400">Total Ideas</h2>
                <p class="mt-1 text-3xl font-semibold text-[#231F20] dark:text-white">{{ \App\Models\Idea::where('user_id', Auth::id())->count() }}</p>
            </div>
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 p-6">
                <h2 class="text-sm font-medium text-[#9B9EA4] dark:text-zinc-400">Draft Ideas</h2>
                <p class="mt-1 text-3xl font-semibold text-[#231F20] dark:text-white">{{ \App\Models\Idea::where('user_id', Auth::id())->where('status', 'draft')->count() }}</p>
            </div>
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 p-6">
                <h2 class="text-sm font-medium text-[#9B9EA4] dark:text-zinc-400">Submitted Ideas</h2>
                <p class="mt-1 text-3xl font-semibold text-[#231F20] dark:text-white">{{ \App\Models\Idea::where('user_id', Auth::id())->where('status', 'submitted')->count() }}</p>
            </div>
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 p-6">
                <h2 class="text-sm font-medium text-[#9B9EA4] dark:text-zinc-400">Ideas in Review</h2>
                <p class="mt-1 text-3xl font-semibold text-[#231F20] dark:text-white">{{ \App\Models\Idea::where('user_id', Auth::id())->where('status', 'in_review')->count() }}</p>
            </div>
        </div>


        <!-- Table Filters -->
        <x-table.filters
            :search="$search"
            searchPlaceholder="Search ideas by title or abstract..."
            :perPage="$perPage"
            :showBulkActions="count($selectedIdeas) > 0"
            :selectedCount="count($selectedIdeas)"
        >
            <!-- Status Filter -->
            <x-slot name="filters">
                <flux:select
                    wire:model.live="status"
                    class="border border-[#9B9EA4]/20 dark:border-zinc-700 rounded-md px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-[#231F20] dark:text-white focus:ring-2 focus:ring-[#FFF200] focus:border-[#FFF200] !w-fit"
                >
                    <flux:select.option value="">All Statuses</flux:select.option>
                    <flux:select.option value="draft">Draft</flux:select.option>
                    <flux:select.option value="submitted">Submitted</flux:select.option>
                </flux:select>

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

            <!-- Bulk Actions -->
            <x-slot name="bulkActions">
                <flux:button wire:click="exportSelected" variant="primary" size="sm">
                    <flux:icon name="arrow-down-tray" class="w-4 h-4 mr-1.5" />
                    Export
                </flux:button>

                <flux:button wire:click="deleteSelected" variant="danger" size="sm">
                    <flux:icon name="trash" class="w-4 h-4 mr-1.5" />
                    Delete
                </flux:button>
            </x-slot>
        </x-table.filters>

        <!-- Bulk Actions Bar -->
        <x-table.bulk-actions
            :selectedIds="$selectedIdeas"
            :actions="[
                [
                    'text' => 'Export Selected',
                    'icon' => 'arrow-down-tray',
                    'wireClick' => 'exportSelected',
                    'variant' => 'secondary'
                ],
                [
                    'text' => 'Delete Selected',
                    'icon' => 'trash',
                    'wireClick' => 'openDeleteSelectedModal',
                    'variant' => 'danger'
                ]
            ]"
        />

        <!-- Main Table -->
        <x-table
            :loading="$this->updatingSearch || $this->updatingStatus || $this->updatingThematicArea"
            :empty="count($this->getIdeas()) === 0 && !$this->updatingSearch && !$this->updatingStatus && !$this->updatingThematicArea"
            emptyTitle="No ideas found"
            emptyDescription="Try adjusting your search or filters to find what you're looking for."
        >
            <!-- Table Header -->
            <x-slot name="head">
                <tr>
                    <th class="px-6 py-3">
                        <flux:checkbox
                            wire:model.live="selectAll"
                            :indeterminate="count($selectedIdeas) > 0 && count($selectedIdeas) < count($this->getIdeas())"
                        />
                    </th>
                    <x-table.column
                        sortable
                        sortField="idea_title"
                        :currentSort="$sortField"
                        :currentDirection="$sortDirection"
                    >
                        {{ __('Title') }}
                    </x-table.column>
                    <x-table.column
                        sortable
                        sortField="thematic_area_id"
                        :currentSort="$sortField"
                        :currentDirection="$sortDirection"
                    >
                        {{ __('Thematic Area') }}
                    </x-table.column>
                    <x-table.column
                        sortable
                        sortField="status"
                        :currentSort="$sortField"
                        :currentDirection="$sortDirection"
                    >
                        {{ __('Status') }}
                    </x-table.column>
                    <x-table.column
                        sortable
                        sortField="created_at"
                        :currentSort="$sortField"
                        :currentDirection="$sortDirection"
                        align="right"
                    >
                        {{ __('Created') }}
                    </x-table.column>
                    <th class="px-6 py-3 text-right">{{ __('Actions') }}</th>
                </tr>
            </x-slot>

            <!-- Table Body -->
            <x-slot name="body">
                @foreach($this->getIdeas() as $idea)
                    <x-table.row
                        :selected="in_array($idea->id, $selectedIdeas)"
                        wire:key="idea-{{ $idea->id }}"
                    >
                        <td class="px-6 py-4">
                            <flux:checkbox
                                wire:model.live="selectedIdeas"
                                value="{{ $idea->id }}"
                            />
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-gradient-to-br from-[#FFF200] to-yellow-300 dark:from-yellow-400 dark:to-yellow-500 flex items-center justify-center">
                                        <flux:icon name="document-text" class="w-5 h-5 text-[#231F20] dark:text-zinc-900" />
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-[#231F20] dark:text-white max-w-xs truncate">
                                        {{ $idea->idea_title }}
                                    </div>
                                    @if($idea->attachment_filename)
                                        <div class="text-xs text-[#9B9EA4] dark:text-zinc-400 flex items-center mt-1">
                                            <flux:icon name="paper-clip" class="w-3 h-3 mr-1" />
                                            {{ $idea->attachment_filename }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-[#231F20] dark:text-white">
                            {{ $idea->thematicArea?->name ?? 'Not specified' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $statusColor = match($idea->status) {
                                    'draft' => 'yellow',
                                    'submitted' => 'green',
                                    default => 'gray'
                                };
                                $statusText = ucfirst($idea->status);
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $statusColor }}-100 dark:bg-{{ $statusColor }}-900/20 text-{{ $statusColor }}-800 dark:text-{{ $statusColor }}-400">
                                {{ $statusText }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-[#9B9EA4] dark:text-zinc-400 text-right">
                            {{ $idea->created_at->format('M j, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end space-x-2">
                                <div class="relative" x-data="{ open: false }">
                                    <flux:tooltip content="More">
                                        <flux:button
                                            icon="ellipsis-vertical"
                                            variant="primary"
                                            size="sm"
                                            color="gray"
                                            @click="open = !open"
                                            @click.away="open = false"
                                        />
                                    </flux:tooltip>

                                    <div
                                        x-show="open"
                                        x-transition
                                        class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white dark:bg-zinc-800 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                                        role="menu"
                                        aria-orientation="vertical"
                                        tabindex="-1"
                                    >
                                        <div class="py-1" role="none">
                                            <a
                                                href="{{ route('ideas.comments', $idea->slug) }}"
                                                class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-700"
                                                role="menuitem"
                                                tabindex="-1"
                                            >
                                                <flux:icon name="chat-bubble-left-right" class="mr-2 h-4 w-4" />
                                                {{ __('View Comments') }}
                                                {{-- total number of comments --}}
                                                <span class="ml-auto inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/20 text-red-800 dark:text-red-400">
                                                    {{ $idea->comments_count ?? 0 }}
                                                </span>
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <flux:tooltip content="View Idea">
                                    <flux:button
                                        icon="eye"
                                        wire:click="viewIdea({{ $idea->id }})"
                                        variant="primary"
                                        size="sm"
                                        class="bg-blue-600 hover:bg-blue-700 text-white dark:bg-blue-500 dark:hover:bg-blue-600"
                                    />
                                </flux:tooltip>

                                @if(in_array($idea->status, ['draft', 'submitted']))
                                    <flux:tooltip content="Edit Idea">
                                        <flux:button
                                            icon="pencil-square"
                                            wire:click="editIdea({{ $idea->id }})"
                                            variant="primary"
                                            size="sm"
                                            class="bg-green-600 hover:bg-green-700 text-white dark:bg-green-500 dark:hover:bg-green-600"
                                        >
                                        </flux:button>
                                    </flux:tooltip>
                                @else
                                    <flux:tooltip content="Editing not allowed at this stage">
                                        <flux:button
                                            icon="pencil-square"
                                            variant="primary"
                                            size="sm"
                                            class="bg-green-600 hover:bg-green-700 text-white dark:bg-green-500 dark:hover:bg-green-600 cursor-not-allowed opacity-50"
                                        >
                                        </flux:button>
                                    </flux:tooltip>
                                @endif

                                {{-- if the idea has the column deleted_at null --}}
                                @if(is_null($idea->deleted_at))
                                    @if(in_array($idea->status, ['draft', 'submitted']))
                                        <flux:tooltip content="Delete Idea">
                                            <flux:button
                                                icon="trash"
                                                wire:click="openDeleteModal({{ $idea->id }})"
                                                variant="danger"
                                                size="sm"
                                            >
                                            </flux:button>
                                        </flux:tooltip>
                                        @else
                                        <flux:tooltip content="Deletion not allowed at this stage">
                                            <flux:button
                                                icon="trash"
                                                variant="danger"
                                                size="sm"
                                                class="cursor-not-allowed opacity-50"
                                            >
                                            </flux:button>
                                        </flux:tooltip>
                                    @endif
                                @else
                                    <flux:tooltip content="Restore Idea">
                                        <flux:button
                                            icon="arrow-uturn-left"
                                            wire:click="restoreIdea({{ $idea->id }})"
                                            variant="primary"
                                            color="pink"
                                            size="sm"
                                        >
                                        </flux:button>
                                    </flux:tooltip>
                                @endif
                            </div>
                        </td>
                    </x-table.row>
                @endforeach
            </x-slot>
        </x-table>

        <!-- Table Pagination -->
        <x-table.pagination :paginator="$this->getIdeas()" />

    </div>

    {{-- Delete Confirmation Modal --}}
    <x-table.delete-modal
        model="showDeleteModal"
        :idea="$deleteIdeaId ? \App\Models\Idea::where('user_id', Auth::id())->find($deleteIdeaId) : null"
        wire-soft-delete="$deleteIdeaId ? 'softDeleteIdea' : 'softDeleteSelected'"
        wire-permanent-delete="$deleteIdeaId ? 'permanentDeleteIdea' : 'permanentDeleteSelected'"
        wire-cancel="closeDeleteModal"
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
</div>