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

    /**
     * Get the ideas for the current user
     */
    public function getIdeas()
    {
        $query = Idea::where('user_id', Auth::id())
            ->with(['thematicArea'])
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
    public function viewIdea(int $ideaId)
    {
        $idea = Idea::where('user_id', Auth::id())->findOrFail($ideaId);
        // For now, redirect to edit if draft, or show read-only view
        if ($idea->status === 'draft') {
            return redirect()->route('ideas.edit_draft.draft', ['draft' => $idea->slug]);
        }
        // TODO: Implement view-only page for submitted ideas
        session()->flash('info', 'View functionality for submitted ideas will be implemented soon.');
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
     * Delete an idea
     */
    public function deleteIdea(int $ideaId): void
    {
        $idea = Idea::where('user_id', Auth::id())->findOrFail($ideaId);

        // Only allow deletion of drafts
        if ($idea->status === 'draft') {
            $idea->delete();
            session()->flash('success', 'Idea deleted successfully.');
            $this->clearSelection();
        } else {
            session()->flash('error', 'Only draft ideas can be deleted.');
        }
    }

    /**
     * Delete selected ideas
     */
    public function deleteSelected(): void
    {
        $ideas = Idea::where('user_id', Auth::id())
            ->whereIn('id', $this->selectedIdeas)
            ->where('status', 'draft')
            ->get();

        if ($ideas->count() > 0) {
            foreach ($ideas as $idea) {
                $idea->delete();
            }
            session()->flash('success', $ideas->count() . ' draft idea(s) deleted successfully.');
        } else {
            session()->flash('error', 'No draft ideas were selected for deletion.');
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
        <div class="text-center space-y-4" x-data="{ show: false }" x-init="setTimeout(() => show = true, 100)">
            <div x-show="show"
                x-transition:enter="transition ease-out duration-1000"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gradient-to-br from-[#FFF200] to-yellow-300 dark:from-yellow-400 dark:to-yellow-500 shadow-lg mx-auto border-2 border-[#231F20] dark:border-zinc-700 mb-6"
            >
                <flux:icon name="light-bulb" class="w-10 h-10 text-[#231F20] dark:text-zinc-900" />
            </div>

            <div x-show="show"
                 x-transition:enter="transition ease-out duration-1000 delay-200"
                 x-transition:enter-start="opacity-0 transform translate-y-4"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
            >
                <h1 class="text-4xl font-bold text-[#231F20] dark:text-white mb-2">
                    My Ideas
                </h1>
                <p class="text-lg text-[#9B9EA4] dark:text-zinc-400 max-w-2xl mx-auto">
                    View and manage all your submitted innovation ideas
                </p>
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
                <select wire:model.live="status" class="border border-[#9B9EA4]/20 dark:border-zinc-700 rounded-md px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-[#231F20] dark:text-white focus:ring-2 focus:ring-[#FFF200] focus:border-[#FFF200]">
                    <option value="">All Statuses</option>
                    <option value="draft">Draft</option>
                    <option value="submitted">Submitted</option>
                </select>

                <select wire:model.live="thematicArea" class="border border-[#9B9EA4]/20 dark:border-zinc-700 rounded-md px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-[#231F20] dark:text-white focus:ring-2 focus:ring-[#FFF200] focus:border-[#FFF200]">
                    <option value="">All Thematic Areas</option>
                    @foreach($this->getThematicAreas() as $area)
                        <option value="{{ $area->id }}">{{ $area->name }}</option>
                    @endforeach
                </select>

                <flux:button wire:click="resetFilters" variant="ghost" size="sm">
                    <flux:icon name="x-mark" class="w-4 h-4 mr-1" />
                    Reset Filters
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
                    'wireClick' => 'deleteSelected',
                    'confirm' => 'Are you sure you want to delete the selected draft ideas?',
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
                        Title
                    </x-table.column>
                    <x-table.column
                        sortable
                        sortField="thematic_area_id"
                        :currentSort="$sortField"
                        :currentDirection="$sortDirection"
                    >
                        Thematic Area
                    </x-table.column>
                    <x-table.column
                        sortable
                        sortField="status"
                        :currentSort="$sortField"
                        :currentDirection="$sortDirection"
                    >
                        Status
                    </x-table.column>
                    <x-table.column
                        sortable
                        sortField="created_at"
                        :currentSort="$sortField"
                        :currentDirection="$sortDirection"
                        align="right"
                    >
                        Created
                    </x-table.column>
                    <th class="px-6 py-3 text-right">Actions</th>
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
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $statusColor }}-100 dark:bg-{{ $statusColor }}-900 text-{{ $statusColor }}-800 dark:text-{{ $statusColor }}-200">
                                {{ $statusText }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-[#9B9EA4] dark:text-zinc-400 text-right">
                            {{ $idea->created_at->format('M j, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end space-x-2">
                                <flux:button wire:click="viewIdea({{ $idea->id }})" variant="ghost" size="sm">
                                    <flux:icon name="eye" class="h-4 w-4" />
                                </flux:button>

                                @if(in_array($idea->status, ['draft', 'submitted']))
                                    <flux:button wire:click="editIdea({{ $idea->id }})" variant="ghost" size="sm">
                                        <flux:icon name="pencil" class="h-4 w-4" />
                                    </flux:button>
                                @endif

                                @if($idea->status === 'draft')
                                    <flux:button wire:click="deleteIdea({{ $idea->id }})" variant="danger" size="sm">
                                        <flux:icon name="trash" class="h-4 w-4" />
                                    </flux:button>
                                @endif
                            </div>
                        </td>
                    </x-table.row>
                @endforeach
            </x-slot>
        </x-table>

        <!-- Table Pagination -->
        <x-table.pagination :paginator="$this->getIdeas()" />

        <!-- Quick Actions -->
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 p-6">
            <h3 class="text-lg font-semibold text-[#231F20] dark:text-white mb-4">Quick Actions</h3>
            <div class="flex flex-wrap gap-3">
                <flux:button href="{{ route('ideas.submit') }}" variant="primary">
                    <flux:icon name="plus" class="w-4 h-4 mr-2" />
                    Submit New Idea
                </flux:button>

                <flux:button wire:click="$refresh" variant="primary">
                    <flux:icon name="arrow-path" class="w-4 h-4 mr-2" />
                    Refresh
                </flux:button>
            </div>
        </div>

    </div>

    <style>
        /* Custom Scrollbar for better UX */
        ::-webkit-scrollbar {
            width: 8px;
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