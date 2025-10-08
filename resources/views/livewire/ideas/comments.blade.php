<?php

use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\Comment;
use App\Models\Idea;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;

new #[Layout('components.layouts.app')] class extends Component {
    public int $ideaId;
    public string $newComment = '';
    public ?int $replyTo = null;
    public string $replyContent = '';
    public bool $showReplyForm = false;

    // UI states
    public bool $loading = false;
    public bool $submitting = false;

    /**
     * Mount the component with the idea ID
     */
    public function mount(int $ideaId): void
    {
        $this->ideaId = $ideaId;
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
     * Get top-level comments (not replies)
     */
    public function getTopLevelComments(): Collection
    {
        return $this->getIdea()->comments->whereNull('parent_id');
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
};
?>

<div class="backdrop-blur-lg min-h-screen bg-gradient-to-br from-[#F8EBD5]/20 via-white to-[#F8EBD5] dark:from-zinc-900/20 dark:via-zinc-800 dark:to-zinc-900 border border-zinc-200 dark:border-yellow-400 rounded-3xl py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto space-y-8">

        <!-- Header Section -->
        <div class="text-center space-y-4" x-data="{ show: false }" x-init="setTimeout(() => show = true, 100)">
            <div x-show="show"
                x-transition:enter="transition ease-out duration-500"
                x-transition:enter-start="opacity-0 scale-90"
                x-transition:enter-end="opacity-100 scale-100"
                class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gradient-to-br from-[#FFF200] to-yellow-300 dark:from-yellow-400 dark:to-yellow-500 shadow-lg mx-auto border-2 border-[#231F20] dark:border-zinc-700 mb-6"
            >
                <flux:icon name="chat-bubble-left-right" class="w-10 h-10 text-[#231F20] dark:text-zinc-900" />
            </div>

            <div x-show="show"
                 x-transition:enter="transition ease-out duration-700 delay-200"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0">
                <h1 class="text-4xl font-bold text-[#231F20] dark:text-white">
                    Comments
                </h1>
                <p class="mt-3 text-lg text-[#9B9EA4] dark:text-zinc-400 max-w-2xl mx-auto">
                    Discuss and collaborate on this innovation idea
                </p>
            </div>
        </div>

        <!-- Idea Info Card -->
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 p-6">
            <div class="flex items-start space-x-4">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-[#FFF200] to-yellow-300 dark:from-yellow-400 dark:to-yellow-500 flex items-center justify-center">
                        <flux:icon name="light-bulb" class="w-6 h-6 text-[#231F20] dark:text-zinc-900" />
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-[#231F20] dark:text-white">
                        {{ $this->getIdea()->idea_title }}
                    </h3>
                    <p class="text-sm text-[#9B9EA4] dark:text-zinc-400 mt-1">
                        {{ Str::limit($this->getIdea()->abstract, 150) }}
                    </p>
                    <div class="flex items-center mt-2 space-x-4 text-xs text-[#9B9EA4] dark:text-zinc-400">
                        <span>By {{ $this->getIdea()->user->first_name }} {{ $this->getIdea()->user->other_names }}</span>
                        <span>•</span>
                        <span>{{ $this->getIdea()->created_at->format('M j, Y') }}</span>
                        @if($this->getIdea()->thematicArea)
                            <span>•</span>
                            <span>{{ $this->getIdea()->thematicArea->name }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Comment Form -->
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl border-2 border-[#9B9EA4]/20 dark:border-zinc-700 overflow-hidden">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-[#231F20] dark:text-white mb-4">
                    Add a Comment
                </h3>

                <form wire:submit="addComment" class="space-y-4">
                    <div>
                        <flux:textarea
                            wire:model="newComment"
                            placeholder="Share your thoughts on this idea..."
                            rows="4"
                            class="w-full border border-[#9B9EA4]/20 dark:border-zinc-700 rounded-lg px-4 py-3 text-[#231F20] dark:text-white bg-white dark:bg-zinc-800 focus:ring-2 focus:ring-[#FFF200] focus:border-[#FFF200] transition-all duration-200 resize-none"
                        />
                        @error('newComment')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end">
                        <flux:button
                            type="submit"
                            wire:loading.attr="disabled"
                            :disabled="$submitting"
                            variant="primary"
                            class="bg-[#FFF200] hover:bg-yellow-400 text-[#231F20] dark:bg-yellow-500 dark:hover:bg-yellow-600 disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="addComment">Post Comment</span>
                            <span wire:loading wire:target="addComment">
                                <flux:icon name="arrow-path" class="w-4 h-4 mr-2 animate-spin" />
                                Posting...
                            </span>
                        </flux:button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Comments List -->
        <div class="space-y-6">
            @forelse($this->getTopLevelComments() as $comment)
                <!-- Comment Card -->
                <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 overflow-hidden">
                    <div class="p-6">
                        <!-- Comment Header -->
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#FFF200] to-yellow-300 dark:from-yellow-400 dark:to-yellow-500 flex items-center justify-center">
                                    <span class="text-sm font-semibold text-[#231F20] dark:text-zinc-900">
                                        {{ substr($comment->user->first_name, 0, 1) }}{{ substr($comment->user->other_names, 0, 1) }}
                                    </span>
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-[#231F20] dark:text-white">
                                        {{ $comment->user->first_name }} {{ $comment->user->other_names }}
                                    </h4>
                                    <p class="text-xs text-[#9B9EA4] dark:text-zinc-400">
                                        {{ $comment->created_at->diffForHumans() }}
                                        @if($comment->read_at)
                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                                Read
                                            </span>
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <!-- Comment Actions -->
                            <div class="flex items-center space-x-2">
                                @if($comment->user_id !== Auth::id())
                                    <flux:button
                                        wire:click="markAsRead({{ $comment->id }})"
                                        variant="ghost"
                                        size="sm"
                                        class="text-[#9B9EA4] hover:text-[#231F20] dark:text-zinc-400 dark:hover:text-white"
                                    >
                                        <flux:icon name="eye" class="w-4 h-4" />
                                    </flux:button>
                                @endif

                                <flux:button
                                    wire:click="showReply({{ $comment->id }})"
                                    variant="ghost"
                                    size="sm"
                                    class="text-[#9B9EA4] hover:text-[#231F20] dark:text-zinc-400 dark:hover:text-white"
                                >
                                    <flux:icon name="chat-bubble-left" class="w-4 h-4" />
                                </flux:button>

                                @if($comment->user_id === Auth::id())
                                    <flux:button
                                        wire:click="deleteComment({{ $comment->id }})"
                                        variant="ghost"
                                        size="sm"
                                        class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                                    >
                                        <flux:icon name="trash" class="w-4 h-4" />
                                    </flux:button>
                                @endif
                            </div>
                        </div>

                        <!-- Comment Content -->
                        <div class="text-[#231F20] dark:text-white mb-4">
                            {{ $comment->content }}
                        </div>

                        <!-- Reply Form -->
                        @if($replyTo === $comment->id && $showReplyForm)
                            <div class="border-t border-[#9B9EA4]/20 dark:border-zinc-700 pt-4 mt-4">
                                <form wire:submit="addReply" class="space-y-3">
                                    <div>
                                        <flux:textarea
                                            wire:model="replyContent"
                                            placeholder="Write a reply..."
                                            rows="3"
                                            class="w-full border border-[#9B9EA4]/20 dark:border-zinc-700 rounded-lg px-4 py-3 text-[#231F20] dark:text-white bg-white dark:bg-zinc-800 focus:ring-2 focus:ring-[#FFF200] focus:border-[#FFF200] transition-all duration-200 resize-none"
                                        />
                                        @error('replyContent')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="flex justify-end space-x-2">
                                        <flux:button
                                            wire:click="hideReply"
                                            variant="ghost"
                                            size="sm"
                                            class="text-[#9B9EA4] hover:text-[#231F20] dark:text-zinc-400 dark:hover:text-white"
                                        >
                                            Cancel
                                        </flux:button>

                                        <flux:button
                                            type="submit"
                                            wire:loading.attr="disabled"
                                            :disabled="$submitting"
                                            variant="primary"
                                            size="sm"
                                            class="bg-[#FFF200] hover:bg-yellow-400 text-[#231F20] dark:bg-yellow-500 dark:hover:bg-yellow-600 disabled:opacity-50"
                                        >
                                            <span wire:loading.remove wire:target="addReply">Reply</span>
                                            <span wire:loading wire:target="addReply">
                                                <flux:icon name="arrow-path" class="w-4 h-4 mr-2 animate-spin" />
                                                Posting...
                                            </span>
                                        </flux:button>
                                    </div>
                                </form>
                            </div>
                        @endif
                    </div>

                    <!-- Replies -->
                    @if($comment->replies->count() > 0)
                        <div class="border-t border-[#9B9EA4]/20 dark:border-zinc-700">
                            @foreach($comment->replies as $reply)
                                <div class="p-6 border-l-2 border-[#FFF200] dark:border-yellow-400 ml-6 bg-[#F8EBD5]/10 dark:bg-zinc-700/20">
                                    <!-- Reply Header -->
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-[#FFF200] to-yellow-300 dark:from-yellow-400 dark:to-yellow-500 flex items-center justify-center">
                                                <span class="text-xs font-semibold text-[#231F20] dark:text-zinc-900">
                                                    {{ substr($reply->user->first_name, 0, 1) }}{{ substr($reply->user->other_names, 0, 1) }}
                                                </span>
                                            </div>
                                            <div>
                                                <h5 class="text-sm font-semibold text-[#231F20] dark:text-white">
                                                    {{ $reply->user->first_name }} {{ $reply->user->other_names }}
                                                </h5>
                                                <p class="text-xs text-[#9B9EA4] dark:text-zinc-400">
                                                    {{ $reply->created_at->diffForHumans() }}
                                                    @if($reply->read_at)
                                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                                            Read
                                                        </span>
                                                    @endif
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Reply Actions -->
                                        <div class="flex items-center space-x-2">
                                            @if($reply->user_id !== Auth::id())
                                                <flux:button
                                                    wire:click="markAsRead({{ $reply->id }})"
                                                    variant="ghost"
                                                    size="sm"
                                                    class="text-[#9B9EA4] hover:text-[#231F20] dark:text-zinc-400 dark:hover:text-white"
                                                >
                                                    <flux:icon name="eye" class="w-3 h-3" />
                                                </flux:button>
                                            @endif

                                            @if($reply->user_id === Auth::id())
                                                <flux:button
                                                    wire:click="deleteComment({{ $reply->id }})"
                                                    variant="ghost"
                                                    size="sm"
                                                    class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                                                >
                                                    <flux:icon name="trash" class="w-3 h-3" />
                                                </flux:button>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Reply Content -->
                                    <div class="text-[#231F20] dark:text-white text-sm">
                                        {{ $reply->content }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <!-- No Comments State -->
                <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 p-12 text-center">
                    <div class="flex flex-col items-center space-y-4">
                        <div class="w-16 h-16 rounded-full bg-gradient-to-br from-[#FFF200] to-yellow-300 dark:from-yellow-400 dark:to-yellow-500 flex items-center justify-center">
                            <flux:icon name="chat-bubble-left-right" class="w-8 h-8 text-[#231F20] dark:text-zinc-900" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-[#231F20] dark:text-white">
                                No comments yet
                            </h3>
                            <p class="text-[#9B9EA4] dark:text-zinc-400 mt-1">
                                Be the first to share your thoughts on this idea!
                            </p>
                        </div>
                    </div>
                </div>
            @endforelse
        </div>

        <!-- Comments Stats -->
        @if($this->getTopLevelComments()->count() > 0)
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center space-x-2">
                            <flux:icon name="chat-bubble-left-right" class="w-5 h-5 text-[#9B9EA4] dark:text-zinc-400" />
                            <span class="text-sm text-[#9B9EA4] dark:text-zinc-400">
                                {{ $this->getTopLevelComments()->count() }} comment{{ $this->getTopLevelComments()->count() !== 1 ? 's' : '' }}
                            </span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <flux:icon name="chat-bubble-left" class="w-5 h-5 text-[#9B9EA4] dark:text-zinc-400" />
                            <span class="text-sm text-[#9B9EA4] dark:text-zinc-400">
                                {{ $this->getIdea()->comments->count() }} total
                            </span>
                        </div>
                    </div>

                    <flux:button
                        wire:click="$refresh"
                        variant="ghost"
                        size="sm"
                        class="text-[#9B9EA4] hover:text-[#231F20] dark:text-zinc-400 dark:hover:text-white"
                    >
                        <flux:icon name="arrow-path" class="w-4 h-4 mr-2" />
                        {{ __('Refresh') }}
                    </flux:button>
                </div>
            </div>
        @endif

    </div>

    <style>
        /* Custom Scrollbar for better UX */
        ::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(155, 158, 164, 0.1);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(to bottom, #FFF200, #f5dc00);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(to bottom, #f5dc00, #e6ce00);
        }

        .dark ::-webkit-scrollbar-thumb {
            background: linear-gradient(to bottom, #facc15, #eab308);
        }

        /* Smooth transitions for all interactive elements */
        * {
            transition-property: color, background-color, border-color, transform, box-shadow;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Input focus enhancement */
        textarea:focus {
            transform: translateY(-1px);
        }
    </style>
</div>