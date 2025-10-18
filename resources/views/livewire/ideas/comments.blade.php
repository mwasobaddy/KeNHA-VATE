<?php

use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Comment;
use App\Models\Idea;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;
    public int $ideaId;
    public string $newComment = '';
    public ?int $replyTo = null;
    public string $replyContent = '';
    public bool $showReplyForm = false;

    // Search and filtering
    public string $search = '';
    public string $filterStatus = '';
    public int $perPage = 10;

    // UI states
    public bool $loading = false;
    public bool $submitting = false;
    public array $expandedReplies = [];
    public bool $showCommentModal = false;

    /**
     * Mount the component with the idea slug
     */
    public function mount(string $idea): void
    {
        $ideaModel = Idea::where('slug', $idea)
            ->firstOrFail();
        $this->ideaId = $ideaModel->id;
    }

    /**
     * Get the idea with comments
     */
    public function getIdea()
    {
        return Idea::with([
            'comments' => function ($query) {
                $query->with(['user', 'replies.user'])
                      ->orderBy('created_at', 'asc');
            }
        ])->findOrFail($this->ideaId);
    }

    /**
     * Get top-level comments (not replies) with search and filters
     */
    public function getTopLevelComments()
    {
        $query = Comment::where('idea_id', $this->ideaId)
            ->whereNull('parent_id')
            ->with(['user', 'replies.user']);

        // Apply search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('content', 'like', '%' . $this->search . '%')
                  ->orWhereHas('user', function ($userQuery) {
                      $userQuery->where('first_name', 'like', '%' . $this->search . '%')
                               ->orWhere('other_names', 'like', '%' . $this->search . '%');
                  });
            });
        }

        // Apply status filter
        if ($this->filterStatus === 'read') {
            $query->whereNotNull('read_at');
        } elseif ($this->filterStatus === 'unread') {
            $query->whereNull('read_at');
        }

        return $query->orderBy('created_at', 'desc')
                    ->paginate($this->perPage);
    }

    /**
     * Add a new comment
     */
    public function addComment(): void
    {
        $this->validate([
            'newComment' => 'required|string|max:1000',
        ]);

        $this->submitting = true;

        try {
            Comment::create([
                'user_id' => Auth::id(),
                'idea_id' => $this->ideaId,
                'content' => $this->newComment,
                'parent_id' => null,
            ]);

            $this->newComment = '';
            $this->showCommentModal = false;
            session()->flash('success', 'Comment added successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to add comment: ' . $e->getMessage());
        }

        $this->submitting = false;
    }

    /**
     * Show reply form for a comment
     */
    public function showReply(int $commentId): void
    {
        $this->replyTo = $commentId;
        $this->showReplyForm = true;
        $this->replyContent = '';
    }

    /**
     * Hide reply form
     */
    public function hideReply(): void
    {
        $this->replyTo = null;
        $this->showReplyForm = false;
        $this->replyContent = '';
    }

    /**
     * Add a reply to a comment
     */
    public function addReply(): void
    {
        $this->validate([
            'replyContent' => 'required|string|max:1000',
        ]);

        $this->submitting = true;

        try {
            Comment::create([
                'user_id' => Auth::id(),
                'idea_id' => $this->ideaId,
                'content' => $this->replyContent,
                'parent_id' => $this->replyTo,
            ]);

            $this->hideReply();
            session()->flash('success', 'Reply added successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to add reply: ' . $e->getMessage());
        }

        $this->submitting = false;
    }

    /**
     * Mark a comment as read
     */
    public function markAsRead(int $commentId): void
    {
        $comment = Comment::where('id', $commentId)
                         ->where('idea_id', $this->ideaId)
                         ->first();

        if ($comment && $comment->user_id !== Auth::id()) {
            $comment->update(['read_at' => now()]);
        }
    }

    /**
     * Delete a comment (only if owned by current user)
     */
    public function deleteComment(int $commentId): void
    {
        $comment = Comment::where('id', $commentId)
                         ->where('user_id', Auth::id())
                         ->first();

        if ($comment) {
            $comment->delete();
            session()->flash('success', 'Comment deleted successfully!');
        } else {
            session()->flash('error', 'You can only delete your own comments.');
        }
    }

    /**
     * Handle search updates
     */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Handle status filter updates
     */
    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    /**
     * Reset all filters
     */
    public function resetFilters(): void
    {
        $this->search = '';
        $this->filterStatus = '';
        $this->resetPage();
    }

    /**
     * Toggle showing all replies for a comment
     */
    public function toggleReplies(int $commentId): void
    {
        if (in_array($commentId, $this->expandedReplies)) {
            $this->expandedReplies = array_diff($this->expandedReplies, [$commentId]);
        } else {
            $this->expandedReplies[] = $commentId;
        }
    }

    /**
     * Toggle comment modal visibility
     */
    public function toggleCommentModal(): void
    {
        $this->showCommentModal = !$this->showCommentModal;
        if (!$this->showCommentModal) {
            $this->newComment = '';
        }
    }
};
?>

<!-- Main Container with improved spacing and modern backdrop -->
<div class="min-h-screen bg-white dark:bg-zinc-900">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
        
        <!-- ============================================
             HERO SECTION - Modern header with icon
             ============================================ -->
        <div class="mb-8 sm:mb-12">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 sm:gap-6">
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
                            <flux:icon name="chat-bubble-left-right" class="w-8 h-8 sm:w-10 sm:h-10 text-[#231F20] dark:text-zinc-900" />
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
                            {{ __('Discussion') }}
                        </h1>
                        <p class="mt-2 text-base sm:text-lg text-[#9B9EA4] dark:text-zinc-400">
                            {{ __('Share insights and collaborate on this innovation') }}
                        </p>
                    </div>
                </div>

                <!-- Quick Action Button - Desktop -->
                <div class="hidden sm:block">
                    <flux:button
                        icon="plus"
                        wire:click="toggleCommentModal"
                        variant="primary"
                        {{-- size="lg" --}}
                        class="rounded-xl bg-[#FFF200] hover:bg-[#FFF200]/90 dark:bg-yellow-400 dark:hover:bg-yellow-300 px-6 py-3 text-[#231F20] dark:text-zinc-900 font-semibold shadow-lg hover:shadow-xl transition-all duration-200"
                    >
                        {{ __('Add Comment') }}
                    </flux:button>
                </div>
            </div>
        </div>

        <!-- ============================================
             IDEA CONTEXT CARD - Enhanced with glassmorphism
             ============================================ -->
        <div 
            class="mb-6 sm:mb-8 group"
            x-data="{ show: false }" 
            x-init="setTimeout(() => show = true, 300)"
            x-show="show"
            x-transition:enter="transition ease-out duration-700"
            x-transition:enter-start="opacity-0 translate-y-4"
            x-transition:enter-end="opacity-100 translate-y-0"
        >
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#F8EBD5]/50 via-white to-[#F8EBD5]/30 dark:from-zinc-800/50 dark:via-zinc-800 dark:to-zinc-800/30 border border-[#9B9EA4]/20 dark:border-zinc-700/50 backdrop-blur-sm shadow-lg hover:shadow-xl transition-all duration-300">
                <!-- Subtle gradient overlay -->
                <div class="absolute inset-0 bg-gradient-to-r from-[#FFF200]/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                
                <div class="relative p-6 sm:p-8">
                    <div class="flex items-start gap-4 sm:gap-6">
                        <!-- Icon -->
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl bg-gradient-to-br from-[#FFF200] to-yellow-300 dark:from-yellow-400 dark:to-yellow-500 flex items-center justify-center shadow-md group-hover:scale-110 transition-transform duration-300">
                                <flux:icon name="light-bulb" class="w-6 h-6 sm:w-7 sm:h-7 text-[#231F20] dark:text-zinc-900" />
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <h2 class="text-xl sm:text-2xl font-bold text-[#231F20] dark:text-white mb-2 line-clamp-2">
                                {{ $this->getIdea()->idea_title }}
                            </h2>
                            <p class="text-sm sm:text-base text-[#9B9EA4] dark:text-zinc-400 mb-4 line-clamp-2">
                                {{ Str::limit($this->getIdea()->abstract, 200) }}
                            </p>
                            
                            <!-- Meta Information -->
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs sm:text-sm text-[#9B9EA4] dark:text-zinc-500">
                                <div class="flex items-center gap-1.5">
                                    <flux:icon name="user" class="w-4 h-4" />
                                    <span>{{ $this->getIdea()->user->first_name }} {{ $this->getIdea()->user->other_names }}</span>
                                </div>
                                <span class="hidden sm:inline">•</span>
                                <div class="flex items-center gap-1.5">
                                    <flux:icon name="calendar" class="w-4 h-4" />
                                    <span>{{ $this->getIdea()->created_at->format('M j, Y') }}</span>
                                </div>
                                @if($this->getIdea()->thematicArea)
                                    <span class="hidden sm:inline">•</span>
                                    <div class="flex items-center gap-1.5">
                                        <flux:icon name="tag" class="w-4 h-4" />
                                        <span>{{ $this->getIdea()->thematicArea->name }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================
             FILTERS & CONTROLS - Modernized toolbar
             ============================================ -->
        <div 
            class="mb-6 sm:mb-8"
            x-data="{ show: false }" 
            x-init="setTimeout(() => show = true, 400)"
            x-show="show"
            x-transition:enter="transition ease-out duration-700"
            x-transition:enter-start="opacity-0 translate-y-4"
            x-transition:enter-end="opacity-100 translate-y-0"
        >
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm overflow-hidden">
                <div class="p-4 sm:p-6">
                    <!-- Mobile: Stacked Layout -->
                    <div class="flex flex-col gap-4">
                        <!-- Search Bar -->
                        <div class="flex-1">
                            <flux:input
                                wire:model.live.debounce.300ms="search"
                                :loading="false"
                                type="text"
                                placeholder="Search comments..."
                                class="w-full rounded-lg bg-zinc-50 dark:bg-zinc-900/50 border-zinc-200 dark:border-zinc-700 text-[#231F20] dark:text-white placeholder:text-[#9B9EA4] dark:placeholder:text-zinc-500 focus:border-[#FFF200] dark:focus:border-yellow-400 focus:ring-2 focus:ring-[#FFF200]/20 dark:focus:ring-yellow-400/20 transition-all duration-200"
                            >
                                <x-slot name="iconTrailing">
                                    <flux:icon name="magnifying-glass" class="w-5 h-5 text-[#9B9EA4] dark:text-zinc-400" />
                                </x-slot>
                            </flux:input>
                        </div>

                        <!-- Controls Row -->
                        <div class="flex flex-wrap items-center gap-3">
                            <!-- Status Filter -->
                            <flux:select
                                wire:model.live="filterStatus"
                                class="!w-fit rounded-lg border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900/50 text-sm text-[#231F20] dark:text-white focus:border-[#FFF200] dark:focus:border-yellow-400 focus:ring-2 focus:ring-[#FFF200]/20 dark:focus:ring-yellow-400/20 transition-all duration-200"
                            >
                                <flux:select.option value="">All Comments</flux:select.option>
                                <flux:select.option value="read">Read</flux:select.option>
                                <flux:select.option value="unread">Unread</flux:select.option>
                            </flux:select>

                            <!-- Per Page -->
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-[#9B9EA4] dark:text-zinc-400 hidden sm:inline">Show</span>
                                <flux:select
                                    wire:model.live="perPage"
                                    class="rounded-lg border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900/50 text-sm text-[#231F20] dark:text-white focus:border-[#FFF200] dark:focus:border-yellow-400 focus:ring-2 focus:ring-[#FFF200]/20 dark:focus:ring-yellow-400/20 transition-all duration-200"
                                >
                                    <flux:select.option value="5">5</flux:select.option>
                                    <flux:select.option value="10">10</flux:select.option>
                                    <flux:select.option value="25">25</flux:select.option>
                                    <flux:select.option value="50">50</flux:select.option>
                                </flux:select>
                            </div>

                            <!-- Add Comment Button - Mobile -->
                            <flux:button
                                wire:click="toggleCommentModal"
                                variant="primary"
                                size="sm"
                                class="sm:hidden rounded-lg bg-[#FFF200] hover:bg-[#FFF200]/90 dark:bg-yellow-400 dark:hover:bg-yellow-300 px-4 py-2 text-[#231F20] dark:text-zinc-900 font-semibold shadow-md hover:shadow-lg transition-all duration-200"
                            >
                                <flux:icon name="plus" class="w-4 h-4 mr-1.5" />
                                Add
                            </flux:button>

                            <!-- Reset Filters -->
                            @if($search || $filterStatus)
                                <flux:button
                                    wire:click="resetFilters"
                                    variant="ghost"
                                    size="sm"
                                    class="rounded-lg text-[#9B9EA4] hover:text-[#231F20] dark:text-zinc-400 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-all duration-200"
                                >
                                    <flux:icon name="x-mark" class="w-4 h-4 mr-1.5" />
                                    Reset
                                </flux:button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================
             ADD COMMENT MODAL - Modern with backdrop blur
             ============================================ -->
        <div
            x-data="{ show: @entangle('showCommentModal') }"
            x-show="show"
            x-on:keydown.escape.window="show = false; $wire.set('showCommentModal', false)"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 overflow-y-auto"
            style="display: none;"
            x-cloak
        >
            <!-- Enhanced Backdrop -->
            <div
                x-show="show"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                class="fixed inset-0 bg-[#231F20]/60 dark:bg-black/70 backdrop-blur-md"
                @click="show = false; $wire.set('showCommentModal', false)"
            ></div>

            <!-- Modal Container -->
            <div class="flex min-h-screen items-center justify-center p-0 sm:p-4">
                <div
                    x-show="show"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-8 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-8 sm:scale-95"
                    class="relative w-full sm:max-w-lg bg-white dark:bg-zinc-800 rounded-t-3xl sm:rounded-2xl shadow-2xl border-t sm:border border-zinc-200 dark:border-zinc-700 overflow-hidden max-h-[90vh] sm:max-h-[85vh] flex flex-col"
                >
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between p-6 border-b border-zinc-200 dark:border-zinc-700 flex-shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#FFF200] to-yellow-300 dark:from-yellow-400 dark:to-yellow-500 flex items-center justify-center shadow-md">
                                <flux:icon name="chat-bubble-left" class="w-5 h-5 text-[#231F20] dark:text-zinc-900" />
                            </div>
                            <h3 class="text-lg sm:text-xl font-bold text-[#231F20] dark:text-white">
                                Add Comment
                            </h3>
                        </div>
                        <button
                            @click="show = false; $wire.set('showCommentModal', false)"
                            class="p-2 rounded-lg text-[#9B9EA4] hover:text-[#231F20] dark:text-zinc-400 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-all duration-200"
                        >
                            <flux:icon name="x-mark" class="w-6 h-6" />
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 overflow-y-auto flex-1">
                        <form wire:submit="addComment" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-[#231F20] dark:text-white mb-2">
                                    Your Comment
                                </label>
                                <flux:textarea
                                    wire:model="newComment"
                                    placeholder="Share your thoughts, feedback, or questions about this innovation..."
                                    rows="5"
                                    class="w-full rounded-xl border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900/50 text-[#231F20] dark:text-white placeholder:text-[#9B9EA4] dark:placeholder:text-zinc-500 focus:border-[#FFF200] dark:focus:border-yellow-400 focus:ring-2 focus:ring-[#FFF200]/20 dark:focus:ring-yellow-400/20 transition-all duration-200 resize-none"
                                />
                                @error('newComment')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1.5">
                                        <flux:icon name="exclamation-circle" class="w-4 h-4" />
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex justify-end gap-3 pt-4">
                                <flux:button
                                    type="button"
                                    @click="show = false; $wire.set('showCommentModal', false)"
                                    variant="ghost"
                                    size="sm"
                                    class="rounded-lg px-4 py-2 text-[#9B9EA4] hover:text-[#231F20] dark:text-zinc-400 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-all duration-200"
                                >
                                    Cancel
                                </flux:button>
                                <flux:button
                                    type="submit"
                                    wire:loading.attr="disabled"
                                    :disabled="$submitting"
                                    variant="primary"
                                    size="sm"
                                    class="rounded-lg bg-[#FFF200] hover:bg-[#FFF200]/90 dark:bg-yellow-400 dark:hover:bg-yellow-300 px-6 py-2 text-[#231F20] dark:text-zinc-900 font-semibold shadow-lg hover:shadow-xl transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <flux:icon name="paper-airplane" class="w-4 h-4 mr-2" />
                                    Post Comment
                                </flux:button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================
             COMMENTS LIST - Instagram-style layout
             ============================================ -->
        <div class="space-y-6">
            @forelse($this->getTopLevelComments() as $index => $comment)
                @php
                    $isCurrentUser = $comment->user_id === Auth::id();
                @endphp
                <div
                    x-data="{ show: false }"
                    x-init="setTimeout(() => show = true, {{ 500 + ($index * 100) }})"
                    x-show="show"
                    x-transition:enter="transition ease-out duration-500"
                    x-transition:enter-start="opacity-0 translate-y-4"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    class="group"
                >
                    <!-- Main Comment -->
                    <div class="flex gap-3">
                        <!-- Avatar -->
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-[#FFF200] via-yellow-300 to-yellow-400 dark:from-yellow-400 dark:via-yellow-500 dark:to-yellow-600 flex items-center justify-center shadow-sm">
                                <span class="text-xs font-bold text-[#231F20] dark:text-zinc-900">
                                    {{ substr($comment->user->first_name, 0, 1) }}{{ substr($comment->user->other_names, 0, 1) }}
                                </span>
                            </div>
                        </div>

                        <!-- Comment Content -->
                        <div class="flex-1 min-w-0">
                            <div class="bg-gray-50 dark:bg-zinc-800 rounded-2xl px-4 py-3 {{ $isCurrentUser ? 'bg-[#FFF200]/10 dark:bg-yellow-400/10' : '' }}">
                                <!-- Username and Content -->
                                <div class="flex items-start gap-2 mb-1">
                                    <span class="font-semibold text-sm text-[#231F20] dark:text-white">
                                        {{ $isCurrentUser ? 'You' : ($comment->user->first_name . ' ' . $comment->user->other_names) }}
                                    </span>
                                    <span class="text-sm text-[#231F20] dark:text-white leading-relaxed break-words flex-1">
                                        {{ $comment->content }}
                                    </span>
                                </div>

                                <!-- Timestamp and Actions -->
                                <div class="flex items-center justify-between mt-2">
                                    <div class="flex items-center gap-4">
                                        <span class="text-xs text-[#9B9EA4] dark:text-zinc-400">
                                            {{ $comment->created_at->diffForHumans() }}
                                        </span>
                                        @if($comment->read_at)
                                            <span class="text-xs text-green-600 dark:text-green-400 font-medium">
                                                Read
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                        @if($comment->user_id !== Auth::id() && !$comment->read_at)
                                            <flux:button
                                                wire:click="markAsRead({{ $comment->id }})"
                                                variant="ghost"
                                                size="sm"
                                                class="p-1 h-6 w-6 text-[#9B9EA4] hover:text-green-600 dark:text-zinc-400 dark:hover:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 transition-all duration-200"
                                            >
                                                <flux:icon name="eye" class="w-3 h-3" />
                                            </flux:button>
                                        @endif

                                        <flux:button
                                            wire:click="showReply({{ $comment->id }})"
                                            variant="ghost"
                                            size="sm"
                                            class="p-1 h-6 w-6 text-[#9B9EA4] hover:text-blue-600 dark:text-zinc-400 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-all duration-200"
                                        >
                                            <span class="text-xs font-medium">Reply</span>
                                        </flux:button>

                                        @if($comment->user_id === Auth::id())
                                            <flux:button
                                                wire:click="deleteComment({{ $comment->id }})"
                                                wire:confirm="Are you sure you want to delete this comment?"
                                                variant="ghost"
                                                size="sm"
                                                class="p-1 h-6 w-6 text-[#9B9EA4] hover:text-red-600 dark:text-zinc-400 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all duration-200"
                                            >
                                                <flux:icon name="trash" class="w-3 h-3" />
                                            </flux:button>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Reply Form -->
                            @if($replyTo === $comment->id && $showReplyForm)
                                <div
                                    class="mt-3 ml-6"
                                    x-data="{ show: true }"
                                    x-show="show"
                                    x-transition:enter="transition ease-out duration-300"
                                    x-transition:enter-start="opacity-0 -translate-y-2"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                >
                                    <div class="flex gap-3">
                                        <div class="flex-shrink-0">
                                            <div class="w-6 h-6 rounded-full bg-gradient-to-br from-[#FFF200] via-yellow-300 to-yellow-400 dark:from-yellow-400 dark:via-yellow-500 dark:to-yellow-600 flex items-center justify-center shadow-sm">
                                                <span class="text-xs font-bold text-[#231F20] dark:text-zinc-900">
                                                    {{ substr(Auth::user()->first_name, 0, 1) }}{{ substr(Auth::user()->other_names, 0, 1) }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <form wire:submit="addReply" class="flex gap-2">
                                                <flux:textarea
                                                    wire:model="replyContent"
                                                    placeholder="Write a reply..."
                                                    rows="1"
                                                    class="flex-1 text-sm rounded-lg border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-[#231F20] dark:text-white placeholder:text-[#9B9EA4] dark:placeholder:text-zinc-500 focus:border-[#FFF200] dark:focus:border-yellow-400 focus:ring-2 focus:ring-[#FFF200]/20 dark:focus:ring-yellow-400/20 transition-all duration-200 resize-none"
                                                />
                                                <div class="flex gap-1">
                                                    <flux:button
                                                        type="button"
                                                        wire:click="hideReply"
                                                        variant="ghost"
                                                        size="sm"
                                                        class="px-3 py-1 text-xs text-[#9B9EA4] hover:text-[#231F20] dark:text-zinc-400 dark:hover:text-white"
                                                    >
                                                        Cancel
                                                    </flux:button>
                                                    <flux:button
                                                        type="submit"
                                                        wire:loading.attr="disabled"
                                                        :disabled="$submitting"
                                                        variant="primary"
                                                        size="sm"
                                                        class="px-3 py-1 text-xs bg-[#FFF200] hover:bg-[#FFF200]/90 dark:bg-yellow-400 dark:hover:bg-yellow-300 text-[#231F20] dark:text-zinc-900 font-semibold"
                                                    >
                                                        Reply
                                                    </flux:button>
                                                </div>
                                            </form>
                                            @error('replyContent')
                                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                                                    {{ $message }}
                                                </p>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Replies Section -->
                            @if($comment->replies->count() > 0)
                                <div class="mt-4 ml-6 space-y-3">
                                    @php
                                        $isExpanded = in_array($comment->id, $this->expandedReplies);
                                        $visibleReplies = $isExpanded ? $comment->replies : $comment->replies->take(2);
                                    @endphp

                                    @foreach($visibleReplies as $reply)
                                        @php
                                            $isReplyCurrentUser = $reply->user_id === Auth::id();
                                        @endphp
                                        <div class="flex gap-3">
                                            <!-- Reply Avatar -->
                                            <div class="flex-shrink-0">
                                                <div class="w-6 h-6 rounded-full bg-gradient-to-br from-[#FFF200] via-yellow-300 to-yellow-400 dark:from-yellow-400 dark:via-yellow-500 dark:to-yellow-600 flex items-center justify-center shadow-sm">
                                                    <span class="text-xs font-bold text-[#231F20] dark:text-zinc-900">
                                                        {{ substr($reply->user->first_name, 0, 1) }}{{ substr($reply->user->other_names, 0, 1) }}
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Reply Content -->
                                            <div class="flex-1 min-w-0">
                                                <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl px-3 py-2 {{ $isReplyCurrentUser ? 'bg-[#FFF200]/10 dark:bg-yellow-400/10' : '' }}">
                                                    <!-- Reply Username and Content -->
                                                    <div class="flex items-start gap-2 mb-1">
                                                        <span class="font-semibold text-xs text-[#231F20] dark:text-white">
                                                            {{ $isReplyCurrentUser ? 'You' : ($reply->user->first_name . ' ' . $reply->user->other_names) }}
                                                        </span>
                                                        <span class="text-xs text-[#231F20] dark:text-white leading-relaxed break-words flex-1">
                                                            {{ $reply->content }}
                                                        </span>
                                                    </div>

                                                    <!-- Reply Timestamp and Actions -->
                                                    <div class="flex items-center justify-between">
                                                        <div class="flex items-center gap-3">
                                                            <span class="text-xs text-[#9B9EA4] dark:text-zinc-400">
                                                                {{ $reply->created_at->diffForHumans() }}
                                                            </span>
                                                            @if($reply->read_at)
                                                                <span class="text-xs text-green-600 dark:text-green-400 font-medium">
                                                                    Read
                                                                </span>
                                                            @endif
                                                        </div>

                                                        <!-- Reply Action Buttons -->
                                                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                                            @if($reply->user_id !== Auth::id() && !$reply->read_at)
                                                                <flux:button
                                                                    wire:click="markAsRead({{ $reply->id }})"
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    class="p-1 h-5 w-5 text-[#9B9EA4] hover:text-green-600 dark:text-zinc-400 dark:hover:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 transition-all duration-200"
                                                                >
                                                                    <flux:icon name="eye" class="w-2.5 h-2.5" />
                                                                </flux:button>
                                                            @endif

                                                            @if($reply->user_id === Auth::id())
                                                                <flux:button
                                                                    wire:click="deleteComment({{ $reply->id }})"
                                                                    wire:confirm="Are you sure you want to delete this reply?"
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    class="p-1 h-5 w-5 text-[#9B9EA4] hover:text-red-600 dark:text-zinc-400 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all duration-200"
                                                                >
                                                                    <flux:icon name="trash" class="w-2.5 h-2.5" />
                                                                </flux:button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach

                                    <!-- Show More/Less Replies Button -->
                                    @if($comment->replies->count() > 2)
                                        <div class="ml-9">
                                            <flux:button
                                                wire:click="toggleReplies({{ $comment->id }})"
                                                variant="ghost"
                                                size="sm"
                                                class="text-xs text-[#9B9EA4] hover:text-[#231F20] dark:text-zinc-400 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-zinc-800 transition-all duration-200"
                                            >
                                                <flux:icon name="{{ $isExpanded ? 'chevron-up' : 'chevron-down' }}" class="w-3 h-3 mr-1" />
                                                {{ $isExpanded ? 'Hide replies' : 'View ' . ($comment->replies->count() - 2) . ' more ' . (($comment->replies->count() - 2) > 1 ? 'replies' : 'reply') }}
                                            </flux:button>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <!-- Empty State - Modern and engaging -->
                <div 
                    x-data="{ show: false }" 
                    x-init="setTimeout(() => show = true, 500)"
                    x-show="show"
                    x-transition:enter="transition ease-out duration-700"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-zinc-200 dark:border-zinc-700 p-12 sm:p-16 text-center"
                >
                    <div class="flex flex-col items-center space-y-6 max-w-md mx-auto">
                        <!-- Animated Icon -->
                        <div class="relative">
                            <div class="absolute inset-0 bg-[#FFF200]/20 dark:bg-yellow-400/20 rounded-full blur-2xl animate-pulse"></div>
                            <div class="relative w-20 h-20 sm:w-24 sm:h-24 rounded-full bg-gradient-to-br from-[#FFF200] via-yellow-300 to-yellow-400 dark:from-yellow-400 dark:via-yellow-500 dark:to-yellow-600 flex items-center justify-center shadow-xl">
                                <flux:icon name="chat-bubble-left-right" class="w-10 h-10 sm:w-12 sm:h-12 text-[#231F20] dark:text-zinc-900" />
                            </div>
                        </div>

                        <!-- Empty State Text -->
                        <div>
                            <h3 class="text-xl sm:text-2xl font-bold text-[#231F20] dark:text-white mb-2">
                                {{ __('No comments yet') }}
                            </h3>
                            <p class="text-sm sm:text-base text-[#9B9EA4] dark:text-zinc-400">
                                {{ __('Start the conversation! Be the first to share your thoughts on this innovation.') }}
                            </p>
                        </div>

                        <!-- CTA Button -->
                        <flux:button
                            icon="plus"
                            wire:click="toggleCommentModal"
                            variant="primary"
                            {{-- size="lg" --}}
                            class="rounded-xl bg-[#FFF200] hover:bg-[#FFF200]/90 dark:bg-yellow-400 dark:hover:bg-yellow-300 px-8 py-3 text-[#231F20] dark:text-zinc-900 font-semibold shadow-lg hover:shadow-xl transition-all duration-200"
                        >
                            {{ __('Add First Comment') }}
                        </flux:button>
                    </div>
                </div>
            @endforelse
        </div>

        <!-- ============================================
             PAGINATION - Modern style
             ============================================ -->
        @if($this->getTopLevelComments()->hasPages())
            <div class="mt-8 sm:mt-12">
                <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm p-4">
                    {{ $this->getTopLevelComments()->links() }}
                </div>
            </div>
        @endif

        <!-- ============================================
             STATS FOOTER - Informative summary
             ============================================ -->
        @if($this->getTopLevelComments()->total() > 0)
            <div 
                class="mt-8 sm:mt-12"
                x-data="{ show: false }" 
                x-init="setTimeout(() => show = true, 600)"
                x-show="show"
                x-transition:enter="transition ease-out duration-700"
                x-transition:enter-start="opacity-0 translate-y-4"
                x-transition:enter-end="opacity-100 translate-y-0"
            >
                <div class="bg-gradient-to-br from-[#F8EBD5]/30 via-white to-[#F8EBD5]/30 dark:from-zinc-800/30 dark:via-zinc-800 dark:to-zinc-800/30 rounded-2xl border border-zinc-200 dark:border-zinc-700 shadow-sm overflow-hidden">
                    <div class="p-6 sm:p-8">
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-6">
                            <!-- Stats -->
                            <div class="flex items-center gap-6 sm:gap-8">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-gradient-to-br from-[#FFF200] to-yellow-300 dark:from-yellow-400 dark:to-yellow-500 flex items-center justify-center shadow-md">
                                        <flux:icon name="chat-bubble-left-right" class="w-5 h-5 sm:w-6 sm:h-6 text-[#231F20] dark:text-zinc-900" />
                                    </div>
                                    <div>
                                        <div class="text-2xl sm:text-3xl font-bold text-[#231F20] dark:text-white">
                                            {{ $this->getTopLevelComments()->total() }}
                                        </div>
                                        <div class="text-xs sm:text-sm text-[#9B9EA4] dark:text-zinc-400">
                                            Top-level comment{{ $this->getTopLevelComments()->total() !== 1 ? 's' : '' }}
                                        </div>
                                    </div>
                                </div>

                                <div class="h-12 w-px bg-zinc-200 dark:bg-zinc-700"></div>

                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-gradient-to-br from-blue-400 to-blue-500 dark:from-blue-500 dark:to-blue-600 flex items-center justify-center shadow-md">
                                        <flux:icon name="chat-bubble-left" class="w-5 h-5 sm:w-6 sm:h-6 text-white" />
                                    </div>
                                    <div>
                                        <div class="text-2xl sm:text-3xl font-bold text-[#231F20] dark:text-white">
                                            {{ $this->getIdea()->comments->count() }}
                                        </div>
                                        <div class="text-xs sm:text-sm text-[#9B9EA4] dark:text-zinc-400">
                                            Total with replies
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Refresh Button -->
                            <flux:button
                                icon="arrow-path"
                                wire:click="$refresh"
                                variant="filled"
                                size="sm"
                                class="rounded-lg px-4 py-2 text-[#9B9EA4] hover:text-[#231F20] dark:text-zinc-400 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-all duration-200"
                            >
                                {{ __('Refresh Comments') }}
                            </flux:button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </div>

    <!-- ============================================
     CUSTOM STYLES - Enhanced scrollbars & animations
     ============================================ -->
    <style>
        /* Modern Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(to bottom, #FFF200, #f5dc00);
            border-radius: 10px;
            transition: background 0.2s ease;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(to bottom, #f5dc00, #e6ce00);
        }

        .dark ::-webkit-scrollbar-thumb {
            background: linear-gradient(to bottom, #facc15, #eab308);
        }

        .dark ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(to bottom, #eab308, #ca8a04);
        }

        /* Smooth transitions for interactive elements */
        input, textarea, button, select {
            transition-property: color, background-color, border-color, transform, box-shadow, opacity;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 200ms;
        }

        /* Focus state enhancement */
        textarea:focus, input:focus {
            transform: translateY(-1px);
        }

        /* Hover lift effect for cards */
        .group:hover {
            transform: translateY(-2px);
        }

        /* Loading state pulse animation */
        @keyframes pulse-soft {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.7;
            }
        }

        [wire\:loading] {
            animation: pulse-soft 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        /* Smooth appearance for paginated content */
        [x-cloak] {
            display: none !important;
        }

        /* Enhanced focus ring with brand colors */
        *:focus-visible {
            outline: 2px solid #FFF200;
            outline-offset: 2px;
        }

        .dark *:focus-visible {
            outline-color: #facc15;
        }

        /* Gradient text effect for headings */
        .gradient-text {
            background: linear-gradient(135deg, #231F20 0%, #9B9EA4 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .dark .gradient-text {
            background: linear-gradient(135deg, #ffffff 0%, #a1a1aa 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Line clamp utilities for better text truncation */
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Mobile-first responsive improvements */
        @media (max-width: 640px) {
            /* Reduce padding on mobile */
            .responsive-padding {
                padding-left: 1rem;
                padding-right: 1rem;
            }

            /* Stack flex items on mobile */
            .mobile-stack {
                flex-direction: column;
            }
        }

        /* Instagram-style comment enhancements */
        .instagram-comment {
            position: relative;
        }

        .instagram-comment:hover .comment-actions {
            opacity: 1;
        }

        .comment-actions {
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .comment-avatar {
            transition: transform 0.2s ease;
        }

        .instagram-comment:hover .comment-avatar {
            transform: scale(1.05);
        }

        .comment-bubble {
            background: #f8f9fa;
            border-radius: 18px;
            position: relative;
        }

        .dark .comment-bubble {
            background: rgb(39 39 42);
        }

        .reply-bubble {
            background: #f1f3f4;
            border-radius: 14px;
        }

        .dark .reply-bubble {
            background: rgb(63 63 70);
        }

        .comment-content {
            line-height: 1.4;
            word-wrap: break-word;
        }

        .comment-username {
            font-weight: 600;
            color: #262626;
        }

        .dark .comment-username {
            color: #f1f1f1;
        }

        .comment-text {
            color: #262626;
            margin-left: 4px;
        }

        .dark .comment-text {
            color: #f1f1f1;
        }

        .comment-timestamp {
            color: #8e8e8e;
            font-size: 12px;
            font-weight: 400;
        }

        .comment-reply-btn {
            color: #8e8e8e;
            font-size: 12px;
            font-weight: 600;
            padding: 0;
            background: none;
            border: none;
            cursor: pointer;
        }

        .comment-reply-btn:hover {
            color: #0095f6;
        }

        .replies-thread {
            border-left: 2px solid #e1e5e9;
            margin-left: 20px;
            padding-left: 16px;
            position: relative;
        }

        .dark .replies-thread {
            border-left-color: rgb(63 63 70);
        }

        .replies-thread::before {
            content: '';
            position: absolute;
            left: -6px;
            top: 0;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #e1e5e9;
        }

        .dark .replies-thread::before {
            background: rgb(63 63 70);
        }

        /* Mobile optimizations */
        @media (max-width: 640px) {
            .comment-bubble {
                border-radius: 16px;
            }

            .reply-bubble {
                border-radius: 12px;
            }

            .comment-avatar {
                width: 32px;
                height: 32px;
            }
        }

        /* Smooth animations */
        .instagram-comment {
            animation: fadeInUp 0.4s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Focus states */
        .comment-reply-btn:focus,
        .comment-action-btn:focus {
            outline: 2px solid #0095f6;
            outline-offset: 2px;
        }
    </style>

    <!-- ============================================
     ALPINE.JS COMPONENT - Auto-scroll for new comments
     ============================================ -->
    <script>
        // Auto-scroll to new comment after submission
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('comment-added', () => {
                setTimeout(() => {
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                }, 100);
            });
        });

        // Enhanced keyboard navigation
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + K to open comment modal
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                Livewire.dispatch('toggle-comment-modal');
            }
        });
    </script>
</div>