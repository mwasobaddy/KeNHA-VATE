<?php

use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\Idea;
use Illuminate\Support\Facades\Auth;

new #[Layout('components.layouts.app')] class extends Component {
    public Idea $idea;

    /**
     * Mount the component with the idea
     */
    public function mount(string $idea): void
    {
        $ideaModel = Idea::where('slug', $idea)->firstOrFail();

        // Ensure the user owns this idea
        if ($ideaModel->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        $this->idea = $ideaModel->load(['thematicArea', 'user.staff']);
    }

    /**
     * Edit the idea (convert to draft if submitted)
     */
    public function editIdea(): void
    {
        if (in_array($this->idea->status, ['draft', 'submitted'])) {
            if ($this->idea->status === 'draft') {
                return redirect()->route('ideas.edit_draft.draft', ['draft' => $this->idea->slug]);
            } else {
                // For submitted ideas, convert back to draft for editing
                $this->idea->update(['status' => 'draft']);
                return redirect()->route('ideas.edit_draft.draft', ['draft' => $this->idea->slug]);
            }
        }

        session()->flash('error', 'This idea cannot be edited as it has reached the final review stage.');
    }

    /**
     * Go back to ideas table
     */
    public function backToIdeas(): void
    {
        return redirect()->route('ideas.table');
    }
};
?>

<div class="backdrop-blur-lg min-h-screen bg-gradient-to-br from-[#F8EBD5]/20 via-white to-[#F8EBD5] dark:from-zinc-900/20 dark:via-zinc-800 dark:to-zinc-900 border border-zinc-200 dark:border-yellow-400 rounded-3xl py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-5xl mx-auto space-y-8">

        <!-- Header Section -->
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <flux:button
                        icon="arrow-left"
                        wire:click="backToIdeas"
                        variant="ghost"
                        class="text-[#231F20] dark:text-white hover:bg-[#F8EBD5] dark:hover:bg-zinc-700"
                    >
                        Back to Ideas
                    </flux:button>

                    <div class="inline-flex items-center justify-center p-3 rounded-full bg-gradient-to-br from-[#FFF200] to-yellow-300 dark:from-yellow-400 dark:to-yellow-500 shadow-lg border-2 border-[#231F20] dark:border-zinc-700">
                        <flux:icon name="document-text" class="w-6 h-6 text-[#231F20] dark:text-zinc-900" />
                    </div>

                    <div>
                        <h1 class="text-3xl font-bold text-[#231F20] dark:text-white">
                            {{ $idea->idea_title }}
                        </h1>
                        <div class="flex items-center gap-4 mt-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @if($idea->status === 'draft') bg-yellow-100 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-400
                                @elseif($idea->status === 'submitted') bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-400
                                @elseif($idea->status === 'in_review') bg-blue-100 dark:bg-blue-900/20 text-blue-800 dark:text-blue-400
                                @else bg-gray-100 dark:bg-gray-900/20 text-gray-800 dark:text-gray-400
                                @endif">
                                {{ ucfirst($idea->status) }}
                            </span>
                            <span class="text-sm text-[#9B9EA4] dark:text-zinc-400">
                                Created {{ $idea->created_at->format('M j, Y \a\t g:i A') }}
                            </span>
                        </div>
                    </div>
                </div>

                @if(in_array($idea->status, ['draft', 'submitted']))
                    <flux:button
                        icon="pencil-square"
                        wire:click="editIdea"
                        variant="primary"
                        class="bg-[#FFF200] hover:bg-yellow-400 text-[#231F20] dark:bg-yellow-500 dark:hover:bg-yellow-600"
                    >
                        Edit Idea
                    </flux:button>
                @endif
            </div>
        </div>

        <!-- Idea Details -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Abstract -->
                <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 p-6">
                    <h2 class="text-xl font-semibold text-[#231F20] dark:text-white mb-4">Abstract</h2>
                    <p class="text-[#9B9EA4] dark:text-zinc-400 leading-relaxed">
                        {{ $idea->abstract }}
                    </p>
                </div>

                <!-- Description -->
                @if($idea->description)
                    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 p-6">
                        <h2 class="text-xl font-semibold text-[#231F20] dark:text-white mb-4">Description</h2>
                        <div class="text-[#9B9EA4] dark:text-zinc-400 leading-relaxed prose prose-sm max-w-none dark:prose-invert">
                            {!! nl2br(e($idea->description)) !!}
                        </div>
                    </div>
                @endif

                <!-- Objectives -->
                @if($idea->objectives)
                    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 p-6">
                        <h2 class="text-xl font-semibold text-[#231F20] dark:text-white mb-4">Objectives</h2>
                        <div class="text-[#9B9EA4] dark:text-zinc-400 leading-relaxed prose prose-sm max-w-none dark:prose-invert">
                            {!! nl2br(e($idea->objectives)) !!}
                        </div>
                    </div>
                @endif

                <!-- Expected Outcomes -->
                @if($idea->expected_outcomes)
                    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 p-6">
                        <h2 class="text-xl font-semibold text-[#231F20] dark:text-white mb-4">Expected Outcomes</h2>
                        <div class="text-[#9B9EA4] dark:text-zinc-400 leading-relaxed prose prose-sm max-w-none dark:prose-invert">
                            {!! nl2br(e($idea->expected_outcomes)) !!}
                        </div>
                    </div>
                @endif

                <!-- Implementation Plan -->
                @if($idea->implementation_plan)
                    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 p-6">
                        <h2 class="text-xl font-semibold text-[#231F20] dark:text-white mb-4">Implementation Plan</h2>
                        <div class="text-[#9B9EA4] dark:text-zinc-400 leading-relaxed prose prose-sm max-w-none dark:prose-invert">
                            {!! nl2br(e($idea->implementation_plan)) !!}
                        </div>
                    </div>
                @endif

                <!-- Budget Estimate -->
                @if($idea->budget_estimate)
                    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 p-6">
                        <h2 class="text-xl font-semibold text-[#231F20] dark:text-white mb-4">Budget Estimate</h2>
                        <p class="text-2xl font-bold text-[#231F20] dark:text-white">
                            KES {{ number_format($idea->budget_estimate, 0) }}
                        </p>
                    </div>
                @endif

                <!-- Timeline -->
                @if($idea->timeline_months)
                    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 p-6">
                        <h2 class="text-xl font-semibold text-[#231F20] dark:text-white mb-4">Timeline</h2>
                        <p class="text-lg text-[#9B9EA4] dark:text-zinc-400">
                            {{ $idea->timeline_months }} month{{ $idea->timeline_months > 1 ? 's' : '' }}
                        </p>
                    </div>
                @endif

            </div>

            <!-- Sidebar -->
            <div class="space-y-6">

                <!-- Thematic Area -->
                <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 p-6">
                    <h3 class="text-lg font-semibold text-[#231F20] dark:text-white mb-3">Thematic Area</h3>
                    <p class="text-[#9B9EA4] dark:text-zinc-400">
                        {{ $idea->thematicArea?->name ?? 'Not specified' }}
                    </p>
                </div>

                <!-- Team Members -->
                @if($idea->team_members && count($idea->team_members) > 0)
                    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 p-6">
                        <h3 class="text-lg font-semibold text-[#231F20] dark:text-white mb-3">Team Members</h3>
                        <div class="space-y-2">
                            @foreach($idea->team_members as $member)
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-[#FFF200] to-yellow-300 dark:from-yellow-400 dark:to-yellow-500 flex items-center justify-center">
                                        <flux:icon name="user" class="w-4 h-4 text-[#231F20] dark:text-zinc-900" />
                                    </div>
                                    <span class="text-sm text-[#9B9EA4] dark:text-zinc-400">{{ $member['name'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Attachment -->
                @if($idea->attachment_filename)
                    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 p-6">
                        <h3 class="text-lg font-semibold text-[#231F20] dark:text-white mb-3">Attachment</h3>
                        <div class="flex items-center gap-3">
                            <flux:icon name="paper-clip" class="w-5 h-5 text-[#9B9EA4] dark:text-zinc-400" />
                            <span class="text-sm text-[#9B9EA4] dark:text-zinc-400">{{ $idea->attachment_filename }}</span>
                        </div>
                    </div>
                @endif

                <!-- Comments -->
                <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 p-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-[#231F20] dark:text-white">Comments</h3>
                        <flux:button
                            wire:click="$redirect('{{ route('ideas.comments', $idea->slug) }}')"
                            variant="primary"
                            size="sm"
                            class="bg-[#FFF200] hover:bg-yellow-400 text-[#231F20] dark:bg-yellow-500 dark:hover:bg-yellow-600"
                        >
                            <flux:icon name="chat-bubble-left-right" class="w-4 h-4 mr-1" />
                            View Comments
                        </flux:button>
                    </div>
                    <p class="text-sm text-[#9B9EA4] dark:text-zinc-400 mt-2">
                        {{ $idea->comments_count ?? 0 }} comment{{ ($idea->comments_count ?? 0) !== 1 ? 's' : '' }}
                    </p>
                </div>

                <!-- Actions -->
                <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 p-6">
                    <h3 class="text-lg font-semibold text-[#231F20] dark:text-white mb-4">Actions</h3>
                    <div class="space-y-3">
                        @if(in_array($idea->status, ['draft', 'submitted']))
                            <flux:button
                                icon="pencil-square"
                                wire:click="editIdea"
                                variant="primary"
                                class="w-full justify-start bg-[#FFF200] hover:bg-yellow-400 text-[#231F20] dark:bg-yellow-500 dark:hover:bg-yellow-600"
                            >
                                Edit Idea
                            </flux:button>
                        @endif

                        <flux:button
                            icon="chat-bubble-left-right"
                            wire:click="$redirect('{{ route('ideas.comments', $idea->slug) }}')"
                            variant="secondary"
                            class="w-full justify-start"
                        >
                            View Comments
                        </flux:button>

                        <flux:button
                            icon="arrow-left"
                            wire:click="backToIdeas"
                            variant="ghost"
                            class="w-full justify-start"
                        >
                            Back to Ideas
                        </flux:button>
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