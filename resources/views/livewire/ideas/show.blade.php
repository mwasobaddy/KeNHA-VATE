<?php

use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\Idea;
use Illuminate\Support\Facades\Auth;

new #[Layout('components.layouts.app')] class extends Component {
    public $idea;

    /**
     * Mount the component with the idea
     */
    public function mount($idea = null): void
    {
        // Diagnostic log for incoming param and current user
        \Log::info('show.mount called', ['incoming' => $idea, 'user_id' => Auth::id()]);

        // Try exact slug match first
        $ideaModel = Idea::where('slug', $idea)->first();

        // Fallback: case-insensitive slug match (SQLite/MySQL collations differ)
        if (!$ideaModel) {
            $lower = mb_strtolower($idea);
            $ideaModel = Idea::whereRaw('lower(slug) = ?', [$lower])->first();
        }

        // Fallback: if the incoming param is numeric, try id lookup
        if (!$ideaModel && is_numeric($idea)) {
            $ideaModel = Idea::find((int) $idea);
        }

        // Ensure the $idea property is always set in the mount method
        if (!$ideaModel) {
            \Log::warning('show.mount: idea not found after fallbacks', ['incoming' => $idea]);
            abort(404);
        }

        // Check if this is a public view (no auth required for collaborative ideas)
        $isPublicView = request()->route() && str_contains(request()->route()->getName(), 'public');

        if ($isPublicView) {
            // For public views, allow access if idea is collaborative and submitted
            if (!$ideaModel->collaboration_enabled || $ideaModel->status !== 'submitted') {
                abort(404, 'Idea not found or not available for public viewing');
            }
        } else {
            // For authenticated views, ensure the user owns this idea
            if ($ideaModel->user_id !== Auth::id()) {
                abort(403, 'Unauthorized');
            }
        }

        // Load relationships AND count active comments
        $this->idea = $ideaModel->load(['thematicArea', 'user.staff']);
        
        // Add comments count - only active (non-disabled) comments
        $this->idea->comments_count = \App\Models\Comment::where('idea_id', $this->idea->id)
            ->where('comment_is_disabled', false)
            ->count();
    }

    /**
     * Edit the idea (convert to draft if submitted)
     */
    public function editIdea(): void
    {
        if (in_array($this->idea->status, ['draft', 'submitted'])) {
            if ($this->idea->status === 'draft') {
                $this->redirect(route('ideas.edit_draft.draft', ['draft' => $this->idea->slug]), navigate: true);
            } else {
                // For submitted ideas, convert back to draft for editing
                $this->idea->update(['status' => 'draft']);
                $this->redirect(route('ideas.edit_draft.draft', ['draft' => $this->idea->slug]), navigate: true);
            }
        } else {
            session()->flash('error', 'This idea cannot be edited as it has reached the final review stage.');
        }
    }

    /**
     * Go back to ideas table or public ideas
     */
    public function backToIdeas(): void
    {
        $isPublicView = request()->route() && str_contains(request()->route()->getName(), 'public');

        if ($isPublicView) {
            $this->redirect(route('ideas.public'), navigate: true);
        } else {
            $this->redirect(route('ideas.table'), navigate: true);
        }
    }

    /**
     * Download the idea PDF
     */
    public function downloadPdf($slug): void
    {
        $url = route('ideas.pdf', $slug);
        $this->js("window.open('{$url}', '_blank')");
    }
};
?>


<div class="backdrop-blur-lg">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 border border-zinc-200 dark:border-yellow-400 rounded-3xl bg-gradient-to-br from-[#F8EBD5]/20 via-white to-[#F8EBD5] dark:from-zinc-900/20 dark:via-zinc-800 dark:to-zinc-900 border">

        <!-- Header Section with Icon -->
        <div class="mb-8 sm:mb-12 gap-6 flex flex-col">
            <div class="flex flex-row justify-between">
                <div>
                    <flux:button
                        icon:trailing="arrow-left"
                        wire:click="backToIdeas"
                        {{-- class="text-[#231F20] dark:text-white hover:bg-[#F8EBD5] dark:hover:bg-zinc-700" --}}
                    >
                        {{ __('Back to Ideas') }}
                    </flux:button>
                </div>
                <div>
                    @php
                        $isPublicView = request()->route() && str_contains(request()->route()->getName(), 'public');
                    @endphp
                    @if(in_array($idea->status, ['draft', 'submitted']) && !$isPublicView)
                        <flux:button
                            icon="pencil-square"
                            wire:click="editIdea"
                            variant="primary"
                            class="bg-[#FFF200] hover:bg-yellow-400 text-[#231F20] dark:bg-yellow-500 dark:hover:bg-yellow-600"
                        >
                            {{ __('Edit Idea') }}
                        </flux:button>
                    @endif
                </div>
            </div>
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
                            {{ $idea->idea_title }}
                        </h1>
                        <p class="mt-2 text-base sm:text-lg text-[#9B9EA4] dark:text-zinc-400">
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
                        </p>
                    </div>
                </div>
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

                <!-- Problem Statement -->
                @if($idea->problem_statement)
                    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 p-6">
                        <h2 class="text-xl font-semibold text-[#231F20] dark:text-white mb-4">Problem Statement</h2>
                        <div class="text-[#9B9EA4] dark:text-zinc-400 leading-relaxed prose prose-sm max-w-none dark:prose-invert">
                            {!! nl2br(e($idea->problem_statement)) !!}
                        </div>
                    </div>
                @endif

                <!-- Proposed Solution -->
                @if($idea->proposed_solution)
                    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 p-6">
                        <h2 class="text-xl font-semibold text-[#231F20] dark:text-white mb-4">Proposed Solution</h2>
                        <div class="text-[#9B9EA4] dark:text-zinc-400 leading-relaxed prose prose-sm max-w-none dark:prose-invert">
                            {!! nl2br(e($idea->proposed_solution)) !!}
                        </div>
                    </div>
                @endif

                <!-- Cost-Benefit Analysis -->
                @if($idea->cost_benefit_analysis)
                    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 p-6">
                        <h2 class="text-xl font-semibold text-[#231F20] dark:text-white mb-4">Cost-Benefit Analysis</h2>
                        <div class="text-[#9B9EA4] dark:text-zinc-400 leading-relaxed prose prose-sm max-w-none dark:prose-invert">
                            {!! nl2br(e($idea->cost_benefit_analysis)) !!}
                        </div>
                    </div>
                @endif

                <!-- Declaration of Interests -->
                @if($idea->declaration_of_interests)
                    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 p-6">
                        <h2 class="text-xl font-semibold text-[#231F20] dark:text-white mb-4">Declaration of Interests</h2>
                        <div class="text-[#9B9EA4] dark:text-zinc-400 leading-relaxed prose prose-sm max-w-none dark:prose-invert">
                            {!! nl2br(e($idea->declaration_of_interests)) !!}
                        </div>
                    </div>
                @endif

                {{-- collabo status, original idea status, team_effort status, status of the idea mini cards --}}
                <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 p-6">
                    <h3 class="text-lg font-semibold text-[#231F20] dark:text-white mb-4">{{ __('More info:') }}</h3>
                    <div class="space-y-3 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex items-center space-x-2">
                            <flux:icon name="users" variant="solid" class="text-[#9B9EA4] dark:text-zinc-400 size-4" />
                            <span>{{ __('Collaboration:') }}</span>
                            @if ($idea->collaboration_enabled === true)
                                <span class="text-green-200 bg-green-900/50 px-4 py-1 rounded-full">{{ __('Yes') }}</span>
                            @else
                                <span class="text-red-200 bg-red-900/50 px-4 py-1 rounded-full">{{ __('No') }}</span>
                            @endif
                        </div>

                        <div class="flex items-center space-x-2">
                            <flux:icon name="shield-exclamation" variant="solid" class="text-[#9B9EA4] dark:text-zinc-400 size-4" />
                            <span>{{ __('Original Idea:') }}</span>
                            @if ($idea->original_idea_disclaimer === true)
                                <span class="text-green-200 bg-green-900/50 px-4 py-1 rounded-full">{{ __('Yes') }}</span>
                            @else
                                <span class="text-red-200 bg-red-900/50 px-4 py-1 rounded-full">{{ __('No') }}</span>
                            @endif
                        </div>

                        <div class="flex items-center space-x-2">
                            <flux:icon name="users" variant="solid" class="text-[#9B9EA4] dark:text-zinc-400 size-4" />
                            <span>{{ __('Team Effort:') }}</span>
                            @if ($idea->team_members !== null)
                                <span class="text-green-200 bg-green-900/50 px-4 py-1 rounded-full">{{ __('Yes') }}</span>
                            @else
                                <span class="text-red-200 bg-red-900/50 px-4 py-1 rounded-full">{{ __('No') }}</span>
                            @endif
                        </div>

                        <div class="flex items-center space-x-2">
                            <flux:icon name="chart-bar-square" variant="solid" class="text-[#9B9EA4] dark:text-zinc-400 size-4" />
                            <span>{{ __('Idea Status:') }}</span>
                            @if ($idea->status !== null)
                                <span class="text-green-200 bg-green-900/50 px-4 py-1 rounded-full">{{ __('Yes') }}</span>
                            @else
                                <span class="text-red-200 bg-red-900/50 px-4 py-1 rounded-full">{{ __('No') }}</span>
                            @endif
                        </div>
                    </div>
                </div>

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

                {{-- PDF View using iframe--}}
                <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 p-6">
                    <h3 class="text-lg font-semibold text-[#231F20] dark:text-white mb-3">PDF View</h3>
                    <div class="flex gap-2 mb-4">
                        <flux:button
                            icon="eye"
                            icon:variant="solid"
                            wire:click="downloadPdf('{{ $idea->slug }}')"
                            download="{{ $idea->attachment_filename ?? 'idea.pdf' }}"
                            {{-- href="{{ route('ideas.pdf-viewer', $idea->slug) }}" --}}
                            target="_blank"
                            variant="primary"
                            size="sm"
                            class="bg-[#FFF200] hover:bg-yellow-400 text-[#231F20] dark:bg-yellow-500 dark:hover:bg-yellow-600"
                        >
                            Preview PDF
                        </flux:button>
                        {{-- <flux:button
                            wire:click="downloadPdf('{{ $idea->slug }}')"
                            download="{{ $idea->attachment_filename ?? 'idea.pdf' }}"
                            variant="primary"
                            size="sm"
                            class="bg-[#FFF200] hover:bg-yellow-400 text-[#231F20] dark:bg-yellow-500 dark:hover:bg-yellow-600"
                        >
                            <flux:icon name="arrow-down-tray" class="w-4 h-4 mr-1" />
                            Download PDF
                        </flux:button> --}}
                    </div>
                </div>

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
                    <div class="flex flex-col justify-between">
                        <h3 class="text-lg font-semibold text-[#231F20] dark:text-white">
                            {{ $idea->comments_count ?? 0 }}&nbsp;{{ __('Comment') }}{{ ($idea->comments_count ?? 0) !== 1 ? 's' : '' }}
                        </h3>
                        <flux:button
                            icon="chat-bubble-left-right"
                            wire:navigate
                            href="{{ route('ideas.comments', $idea->slug) }}"
                            variant="primary"
                            size="sm"
                            class="w-full justify-start bg-[#FFF200] hover:bg-yellow-400 text-[#231F20] dark:bg-yellow-500 dark:hover:bg-yellow-600"
                        >
                            {{ __('View Comments') }}
                        </flux:button>
                    </div>
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
                            variant="primary"
                            class="w-full justify-start"
                        >
                            {{ __('View Comments') }}
                        </flux:button>

                        <flux:button
                            icon="arrow-left"
                            wire:click="backToIdeas"
                            class="w-full justify-start"
                        >
                            {{ __('Back to Ideas') }}
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