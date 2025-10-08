<?php

use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\Idea;
use App\Services\NotificationService;
use App\Services\AuditService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

new #[Layout('components.layouts.app')] class extends Component {
    public string $idea_title = '';
    public string $thematic_area_id = '';
    public string $abstract = '';
    public string $problem_statement = '';
    public string $proposed_solution = '';
    public string $cost_benefit_analysis = '';
    public string $declaration_of_interests = '';
    public bool $original_idea_disclaimer = false;
    public $attachment;
    public bool $collaboration_enabled = false;
    public bool $team_effort = false;
    public array $team_members = [];

    // Team member fields for dynamic addition
    public string $team_member_name = '';
    public string $team_member_email = '';
    public string $team_member_role = '';
    
    // Current step tracking
    public int $currentStep = 1;

    // UI flags
    public bool $saving = false;
    public bool $submitted = false;

    /**
     * Save the idea as a draft.
     */
    public function saveDraft(): void
    {
        Log::info('Starting draft save', [
            'user_id' => Auth::id(),
            'idea_title' => $this->idea_title,
            'thematic_area_id' => $this->thematic_area_id,
        ]);

        $this->validate([
            'idea_title' => 'required|string|max:255',
            'thematic_area_id' => 'nullable|integer|exists:thematic_areas,id',
        ]);

        Log::info('Draft validation passed', ['user_id' => Auth::id()]);

        $this->saving = true;

        try {
            // Check if user already has a draft
            $idea = Idea::where('user_id', Auth::id())
                       ->where('status', 'draft')
                       ->first();

            Log::info('Draft lookup result', [
                'user_id' => Auth::id(),
                'existing_draft_found' => $idea ? true : false,
                'existing_draft_id' => $idea?->id,
            ]);

            if (!$idea) {
                $idea = new Idea();
                $idea->user_id = Auth::id();
                Log::info('Creating new draft idea', ['user_id' => Auth::id()]);
            } else {
                Log::info('Updating existing draft idea', [
                    'user_id' => Auth::id(),
                    'idea_id' => $idea->id,
                ]);
            }

            $idea->idea_title = $this->idea_title;
            $idea->thematic_area_id = $this->thematic_area_id ?: null;
            $idea->abstract = $this->abstract ?: null;
            $idea->problem_statement = $this->problem_statement ?: null;
            $idea->proposed_solution = $this->proposed_solution ?: null;
            $idea->cost_benefit_analysis = $this->cost_benefit_analysis ?: null;
            $idea->declaration_of_interests = $this->declaration_of_interests ?: null;
            $idea->original_idea_disclaimer = (bool) $this->original_idea_disclaimer;
            $idea->collaboration_enabled = (bool) $this->collaboration_enabled;
            $idea->team_effort = (bool) $this->team_effort;
            $idea->team_members = $this->team_members ?: null;
            $idea->status = 'draft';

            // Handle attachment if Livewire bound
            if (!empty($this->attachment) && is_object($this->attachment)) {
                try {
                    $file = $this->attachment; // UploadedFile
                    $idea->attachment = file_get_contents($file->getRealPath());
                    $idea->attachment_filename = $file->getClientOriginalName();
                    $idea->attachment_mime = $file->getClientMimeType();
                    $idea->attachment_size = $file->getSize();
                    Log::info('Attachment processed for draft', [
                        'user_id' => Auth::id(),
                        'filename' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Failed to process attachment for draft', [
                        'user_id' => Auth::id(),
                        'error' => $e->getMessage(),
                    ]);
                    // ignore attachment errors for draft save
                }
            }

            $idea->save();

            Log::info('Draft saved successfully', [
                'user_id' => Auth::id(),
                'idea_id' => $idea->id,
                'idea_title' => $idea->idea_title,
            ]);

            // Notify user
            if ($user = Auth::user()) {
                app(NotificationService::class)->info(
                    $user,
                    'Draft saved',
                    'Your idea has been saved as a draft.',
                    route('ideas.submit')
                );
            }

            // Audit log
            app(AuditService::class)->log(
                'idea.saved_draft',
                Auth::id(),
                ['title' => $idea->idea_title],
                null,
                'ideas',
                $idea->id
            );

            session()->flash('success', 'Draft saved successfully!');
            
        } catch (\Exception $e) {
            Log::error('Failed to save draft', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            session()->flash('error', 'Failed to save draft: ' . $e->getMessage());
        }

        $this->saving = false;
        Log::info('Draft save process completed', ['user_id' => Auth::id()]);
    }

    /**
     * Submit the idea (finalize and mark as submitted).
     */
    public function submitIdea(): void
    {
        Log::info('Starting idea submission', [
            'user_id' => Auth::id(),
            'idea_title' => $this->idea_title,
            'thematic_area_id' => $this->thematic_area_id,
            'has_attachment' => !empty($this->attachment),
        ]);

        // Validate required fields for submission
        $this->validate([
            'idea_title' => 'required|string|max:255',
            'thematic_area_id' => 'required|integer|exists:thematic_areas,id',
            'abstract' => 'required|string|max:1000',
            'problem_statement' => 'required|string|max:2000',
            'proposed_solution' => 'required|string|max:2000',
            'cost_benefit_analysis' => 'required|string|max:2000',
            'declaration_of_interests' => 'required|string|max:1000',
            'original_idea_disclaimer' => 'required|boolean|accepted',
        ]);

        Log::info('Idea submission validation passed', ['user_id' => Auth::id()]);

        $this->submitted = true;

        try {
            // Check if user has a draft to update, otherwise create new
            $idea = Idea::where('user_id', Auth::id())
                       ->where('status', 'draft')
                       ->first();

            Log::info('Submission draft lookup result', [
                'user_id' => Auth::id(),
                'existing_draft_found' => $idea ? true : false,
                'existing_draft_id' => $idea?->id,
            ]);

            if (!$idea) {
                $idea = new Idea();
                $idea->user_id = Auth::id();
                Log::info('Creating new idea for submission', ['user_id' => Auth::id()]);
            } else {
                Log::info('Converting draft to submitted idea', [
                    'user_id' => Auth::id(),
                    'idea_id' => $idea->id,
                ]);
            }

            $idea->idea_title = $this->idea_title;
            $idea->thematic_area_id = $this->thematic_area_id;
            $idea->abstract = $this->abstract;
            $idea->problem_statement = $this->problem_statement;
            $idea->proposed_solution = $this->proposed_solution;
            $idea->cost_benefit_analysis = $this->cost_benefit_analysis;
            $idea->declaration_of_interests = $this->declaration_of_interests;
            $idea->original_idea_disclaimer = (bool) $this->original_idea_disclaimer;
            $idea->collaboration_enabled = (bool) $this->collaboration_enabled;
            $idea->team_effort = (bool) $this->team_effort;
            $idea->team_members = $this->team_members ?: null;
            $idea->status = 'submitted';

            // Handle attachment
            if (!empty($this->attachment) && is_object($this->attachment)) {
                try {
                    $file = $this->attachment;
                    $idea->attachment = file_get_contents($file->getRealPath());
                    $idea->attachment_filename = $file->getClientOriginalName();
                    $idea->attachment_mime = $file->getClientMimeType();
                    $idea->attachment_size = $file->getSize();
                    Log::info('Attachment processed for submission', [
                        'user_id' => Auth::id(),
                        'filename' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                    ]);
                } catch (\Throwable $e) {
                    Log::error('Failed to process attachment for submission', [
                        'user_id' => Auth::id(),
                        'error' => $e->getMessage(),
                    ]);
                    session()->flash('error', 'Failed to process attachment: ' . $e->getMessage());
                    $this->submitted = false;
                    return;
                }
            }

            $idea->save();

            Log::info('Idea submitted successfully', [
                'user_id' => Auth::id(),
                'idea_id' => $idea->id,
                'idea_title' => $idea->idea_title,
                'thematic_area_id' => $idea->thematic_area_id,
            ]);

            // Notify user
            if ($user = Auth::user()) {
                app(NotificationService::class)->success(
                    $user,
                    'Idea submitted',
                    'Thank you — your idea has been submitted for review.',
                    route('ideas.submit')
                );
            }

            // Audit log
            app(AuditService::class)->log(
                'idea.submitted',
                Auth::id(),
                ['title' => $idea->idea_title],
                null,
                'ideas',
                $idea->id
            );

            session()->flash('success', 'Idea submitted successfully!');

        } catch (\Exception $e) {
            Log::error('Failed to submit idea', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            session()->flash('error', 'Failed to submit idea: ' . $e->getMessage());
            $this->submitted = false;
        }

        Log::info('Idea submission process completed', ['user_id' => Auth::id()]);
    }
}; ?>

<div class="backdrop-blur-lg min-h-screen bg-gradient-to-br from-[#F8EBD5]/20 via-white to-[#F8EBD5] dark:from-zinc-900/20 dark:via-zinc-800 dark:to-zinc-900 border border-zinc-200 dark:border-yellow-400 rounded-3xl py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-5xl mx-auto space-y-8">
        
        <!-- Header Section with Icon -->
        <div class="text-center space-y-4" x-data="{ show: false }" x-init="setTimeout(() => show = true, 100)">
            <div x-show="show" 
                 x-transition:enter="transition ease-out duration-500"
                 x-transition:enter-start="opacity-0 scale-90"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gradient-to-br from-[#FFF200] to-yellow-300 dark:from-yellow-400 dark:to-yellow-500 shadow-lg mx-auto mb-6">
                <svg class="w-10 h-10 text-[#231F20] dark:text-zinc-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
            </div>
            
            <div x-show="show" 
                 x-transition:enter="transition ease-out duration-700 delay-200"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0">
                <h1 class="text-4xl font-bold text-[#231F20] dark:text-white">
                    {{ __('Share Your Innovation') }}
                </h1>
                <p class="mt-3 text-lg text-[#9B9EA4] dark:text-zinc-400 max-w-3xl mx-auto">
                    {{ __('Transform Kenya\'s road sector with your groundbreaking ideas. Every innovation starts with a single submission.') }}
                </p>
            </div>
        </div>

        <!-- Enhanced Progress Indicator -->
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 p-6 mb-8">
            <div class="flex items-center justify-between" x-data="{ currentStep: @entangle('currentStep') }">
                
                <!-- Step 1: Basic Information -->
                <div class="flex items-center flex-1">
                    <div class="flex items-center">
                        <div :class="currentStep >= 1 ? 'bg-[#FFF200] dark:bg-yellow-400 text-[#231F20] dark:text-zinc-900' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400'" 
                             class="w-12 h-12 rounded-full flex items-center justify-center font-bold text-base shadow-lg transition-all duration-300">
                            <span x-show="currentStep > 1">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </span>
                            <span x-show="currentStep <= 1">1</span>
                        </div>
                    </div>
                    <div class="ml-4 hidden sm:block">
                        <p :class="currentStep >= 1 ? 'text-[#231F20] dark:text-white' : 'text-zinc-600 dark:text-zinc-400'" 
                           class="text-sm font-semibold transition-colors duration-300">Basic Info</p>
                        <p class="text-xs text-[#9B9EA4] dark:text-zinc-500">Title & Category</p>
                    </div>
                </div>
                
                <!-- Connector Line -->
                <div class="flex-1 h-1 mx-4 rounded-full overflow-hidden bg-zinc-200 dark:bg-zinc-700">
                    <div :style="`width: ${currentStep > 1 ? '100%' : '0%'}`" 
                         class="h-full bg-gradient-to-r from-[#FFF200] to-yellow-300 dark:from-yellow-400 dark:to-yellow-500 transition-all duration-500"></div>
                </div>

                <!-- Step 2: Details -->
                <div class="flex items-center flex-1">
                    <div class="flex items-center">
                        <div :class="currentStep >= 2 ? 'bg-[#FFF200] dark:bg-yellow-400 text-[#231F20] dark:text-zinc-900' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400'" 
                             class="w-12 h-12 rounded-full flex items-center justify-center font-bold text-base shadow-lg transition-all duration-300">
                            <span x-show="currentStep > 2">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </span>
                            <span x-show="currentStep <= 2">2</span>
                        </div>
                    </div>
                    <div class="ml-4 hidden sm:block">
                        <p :class="currentStep >= 2 ? 'text-[#231F20] dark:text-white' : 'text-zinc-600 dark:text-zinc-400'" 
                           class="text-sm font-semibold transition-colors duration-300">Details</p>
                        <p class="text-xs text-[#9B9EA4] dark:text-zinc-500">Problem & Solution</p>
                    </div>
                </div>

                <!-- Connector Line -->
                <div class="flex-1 h-1 mx-4 rounded-full overflow-hidden bg-zinc-200 dark:bg-zinc-700">
                    <div :style="`width: ${currentStep > 2 ? '100%' : '0%'}`" 
                         class="h-full bg-gradient-to-r from-[#FFF200] to-yellow-300 dark:from-yellow-400 dark:to-yellow-500 transition-all duration-500"></div>
                </div>

                <!-- Step 3: Review & Submit -->
                <div class="flex items-center flex-1">
                    <div class="flex items-center">
                        <div :class="currentStep >= 3 ? 'bg-[#FFF200] dark:bg-yellow-400 text-[#231F20] dark:text-zinc-900' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400'" 
                             class="w-12 h-12 rounded-full flex items-center justify-center font-bold text-base shadow-lg transition-all duration-300">
                            3
                        </div>
                    </div>
                    <div class="ml-4 hidden sm:block">
                        <p :class="currentStep >= 3 ? 'text-[#231F20] dark:text-white' : 'text-zinc-600 dark:text-zinc-400'" 
                           class="text-sm font-semibold transition-colors duration-300">Finalize</p>
                        <p class="text-xs text-[#9B9EA4] dark:text-zinc-500">Review & Submit</p>
                    </div>
                </div>
            </div>
        </div>

        <form class="space-y-6">
            
            <!-- Section 1: Basic Information -->
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl border-2 border-[#9B9EA4]/20 dark:border-zinc-700 overflow-hidden transition-all duration-300 hover:shadow-2xl">
                
                <!-- Accent Bar -->
                <div class="h-2 bg-gradient-to-r from-[#FFF200] via-yellow-300 to-[#FFF200] dark:from-yellow-400 dark:via-yellow-500 dark:to-yellow-400"></div>
                
                <div class="p-8 space-y-6">
                    <!-- Section Header -->
                    <div class="flex items-center justify-between pb-6 border-b border-[#9B9EA4]/20 dark:border-zinc-700">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 rounded-xl bg-[#FFF200]/10 dark:bg-yellow-400/10 flex items-center justify-center">
                                <svg class="w-6 h-6 text-[#FFF200] dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-[#231F20] dark:text-white">
                                    {{ __('Basic Information') }}
                                </h2>
                                <p class="text-sm text-[#9B9EA4] dark:text-zinc-400 mt-1">
                                    {{ __('Start with the fundamentals of your innovative idea') }}
                                </p>
                            </div>
                        </div>
                        <div class="hidden sm:flex items-center space-x-2 text-sm text-[#9B9EA4] dark:text-zinc-400">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                            </svg>
                            <span>~3 min</span>
                        </div>
                    </div>

                    <!-- Idea Title -->
                    <div class="space-y-2">
                        <div class="relative">
                            <flux:input
                                wire:model.live.debounce.500ms="idea_title"
                                :label="__('Idea Title')"
                                type="text"
                                :loading="false"
                                required
                                maxlength="35"
                                placeholder="e.g., AI-Powered Pothole Detection System"
                                class="w-full text-lg"
                            />
                            <div class="absolute right-3 top-11 flex items-center space-x-2">
                                <span :class="$wire.idea_title.length >= 35 ? 'text-red-500' : 'text-[#9B9EA4] dark:text-zinc-400'" 
                                      class="text-xs font-medium">
                                    {{ strlen($idea_title) }}/35
                                </span>
                            </div>
                        </div>
                        <p class="text-xs text-[#9B9EA4] dark:text-zinc-400 flex items-start space-x-1">
                            <svg class="w-3 h-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <span>{{ __('Make it concise yet descriptive. Think elevator pitch.') }}</span>
                        </p>
                    </div>

                    <!-- Thematic Area -->
                    <div class="space-y-2">
                        @php
                            $thematicAreas = \App\Models\ThematicArea::active()->ordered()->get();
                        @endphp
                        <flux:select
                            wire:model="thematic_area_id"
                            :label="__('Thematic Area')"
                            placeholder="Select a thematic area"
                            required
                            class="w-full"
                        >
                            <option value="">{{ __('Choose the most relevant category...') }}</option>
                            @foreach($thematicAreas as $area)
                                <option value="{{ $area->id }}">{{ $area->name }}</option>
                            @endforeach
                        </flux:select>
                        <p class="text-xs text-[#9B9EA4] dark:text-zinc-400 flex items-start space-x-1">
                            <svg class="w-3 h-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <span>{{ __('Select the primary focus area for proper evaluation routing.') }}</span>
                        </p>
                    </div>

                    <!-- Abstract -->
                    <div class="space-y-2">
                        <flux:textarea
                            wire:model.live.debounce.500ms="abstract"
                            :label="__('Abstract')"
                            required
                            rows="5"
                            placeholder="Provide a compelling one-paragraph summary that captures the essence of your idea, its objectives, and expected outcomes..."
                            class="w-full"
                        />
                        <div class="flex items-center justify-between text-xs">
                            <p class="text-[#9B9EA4] dark:text-zinc-400 flex items-start space-x-1">
                                <svg class="w-3 h-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                                <span>{{ __('Think: What, Why, How, and Expected Impact') }}</span>
                            </p>
                            <span class="text-[#9B9EA4] dark:text-zinc-400 font-medium">
                                {{ str_word_count($abstract) }} words
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Detailed Information -->
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl border-2 border-[#9B9EA4]/20 dark:border-zinc-700 overflow-hidden transition-all duration-300 hover:shadow-2xl">
                
                <!-- Accent Bar -->
                <div class="h-2 bg-gradient-to-r from-[#FFF200] via-yellow-300 to-[#FFF200] dark:from-yellow-400 dark:via-yellow-500 dark:to-yellow-400"></div>
                
                <div class="p-8 space-y-6">
                    <!-- Section Header -->
                    <div class="flex items-center justify-between pb-6 border-b border-[#9B9EA4]/20 dark:border-zinc-700">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 rounded-xl bg-[#FFF200]/10 dark:bg-yellow-400/10 flex items-center justify-center">
                                <svg class="w-6 h-6 text-[#FFF200] dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-[#231F20] dark:text-white">
                                    {{ __('Detailed Information') }}
                                </h2>
                                <p class="text-sm text-[#9B9EA4] dark:text-zinc-400 mt-1">
                                    {{ __('Dive deep into the problem, solution, and implementation') }}
                                </p>
                            </div>
                        </div>
                        <div class="hidden sm:flex items-center space-x-2 text-sm text-[#9B9EA4] dark:text-zinc-400">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                            </svg>
                            <span>~7 min</span>
                        </div>
                    </div>

                    <!-- Problem Statement -->
                    <div class="space-y-2">
                        <div class="flex items-center space-x-2 mb-3">
                            <div class="w-8 h-8 rounded-lg bg-red-100 dark:bg-red-900/20 flex items-center justify-center">
                                <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <label class="text-base font-semibold text-[#231F20] dark:text-white">
                                {{ __('Problem Statement') }}
                            </label>
                        </div>
                        <flux:textarea
                            wire:model="problem_statement"
                            required
                            rows="5"
                            placeholder="What specific challenge or pain point does your idea address? Be clear about the scope, impact, and urgency of the problem..."
                            class="w-full"
                        />
                        <p class="text-xs text-[#9B9EA4] dark:text-zinc-400 flex items-start space-x-1">
                            <svg class="w-3 h-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <span>{{ __('Include data, statistics, or real-world examples if available') }}</span>
                        </p>
                    </div>

                    <!-- Proposed Solution -->
                    <div class="space-y-2">
                        <div class="flex items-center space-x-2 mb-3">
                            <div class="w-8 h-8 rounded-lg bg-green-100 dark:bg-green-900/20 flex items-center justify-center">
                                <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <label class="text-base font-semibold text-[#231F20] dark:text-white">
                                {{ __('Proposed Solution') }}
                            </label>
                        </div>
                        <flux:textarea
                            wire:model="proposed_solution"
                            required
                            rows="5"
                            placeholder="Describe your innovative solution in detail. How does it work? What makes it unique? What are the key features and benefits..."
                            class="w-full"
                        />
                        <p class="text-xs text-[#9B9EA4] dark:text-zinc-400 flex items-start space-x-1">
                            <svg class="w-3 h-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <span>{{ __('Focus on feasibility, innovation, and measurable outcomes') }}</span>
                        </p>
                    </div>

                    <!-- Cost Benefit Analysis -->
                    <div class="space-y-2">
                        <div class="flex items-center space-x-2 mb-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/20 flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <label class="text-base font-semibold text-[#231F20] dark:text-white">
                                {{ __('Cost-Benefit Analysis') }}
                            </label>
                        </div>
                        <flux:textarea
                            wire:model="cost_benefit_analysis"
                            required
                            rows="6"
                            placeholder="Break down the implementation costs, expected benefits, potential risks, and mitigation strategies. Include timeframes and ROI estimates if possible..."
                            class="w-full"
                        />
                        <p class="text-xs text-[#9B9EA4] dark:text-zinc-400 flex items-start space-x-1">
                            <svg class="w-3 h-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <span>{{ __('Be realistic about costs and conservative with benefit projections') }}</span>
                        </p>
                    </div>

                    <!-- Declaration of Interests -->
                    <div class="space-y-2">
                        <div class="flex items-center space-x-2 mb-3">
                            <div class="w-8 h-8 rounded-lg bg-purple-100 dark:bg-purple-900/20 flex items-center justify-center">
                                <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <label class="text-base font-semibold text-[#231F20] dark:text-white">
                                {{ __('Declaration of Interests') }}
                            </label>
                        </div>
                        <flux:textarea
                            wire:model="declaration_of_interests"
                            required
                            rows="4"
                            placeholder="Declare any personal or corporate interests in this idea. What role do you envision playing if this idea is implemented? Are there any potential conflicts of interest..."
                            class="w-full"
                        />
                        <p class="text-xs text-[#9B9EA4] dark:text-zinc-400 flex items-start space-x-1">
                            <svg class="w-3 h-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <span>{{ __('Transparency builds trust. Be honest about your involvement.') }}</span>
                        </p>
                    </div>

                    <!-- Original Idea Disclaimer -->
                    <div class="bg-[#F8EBD5] dark:bg-zinc-900/50 rounded-xl p-6 border-l-4 border-[#FFF200] dark:border-yellow-400">
                        <div class="flex items-center gap-2">
                            <flux:checkbox
                                wire:model.live="original_idea_disclaimer"
                                required
                            >
                            </flux:checkbox>
                            <span class="text-sm font-medium text-[#231F20] dark:text-white">
                                {{ __('I declare that this idea is original and has not been submitted elsewhere') }}
                            </span>
                        </div>
                        <p class="text-xs text-[#9B9EA4] dark:text-zinc-400 mt-2 ml-6">
                            {{ __('This ensures integrity and prevents duplicate submissions') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Section 3: Attachments & Collaboration -->
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl border-2 border-[#9B9EA4]/20 dark:border-zinc-700 overflow-hidden transition-all duration-300 hover:shadow-2xl">
                
                <!-- Accent Bar -->
                <div class="h-2 bg-gradient-to-r from-[#FFF200] via-yellow-300 to-[#FFF200] dark:from-yellow-400 dark:via-yellow-500 dark:to-yellow-400"></div>
                
                <div class="p-8 space-y-6">
                    <!-- Section Header -->
                    <div class="flex items-center justify-between pb-6 border-b border-[#9B9EA4]/20 dark:border-zinc-700">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 rounded-xl bg-[#FFF200]/10 dark:bg-yellow-400/10 flex items-center justify-center">
                                <svg class="w-6 h-6 text-[#FFF200] dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-[#231F20] dark:text-white">
                                    {{ __('Attachments & Collaboration') }}
                                </h2>
                                <p class="text-sm text-[#9B9EA4] dark:text-zinc-400 mt-1">
                                    {{ __('Add supporting documents and configure team settings') }}
                                </p>
                            </div>
                        </div>
                        <div class="hidden sm:flex items-center space-x-2 text-sm text-[#9B9EA4] dark:text-zinc-400">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                            </svg>
                            <span>~2 min</span>
                        </div>
                    </div>

                    <!-- File Upload with Enhanced UI -->
                    <div x-data="fileUpload()" x-init="init()" class="space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="text-base font-semibold text-[#231F20] dark:text-white">
                                {{ __('Supporting Document') }}
                            </label>
                            <span class="text-xs text-[#9B9EA4] dark:text-zinc-400 bg-[#F8EBD5] dark:bg-zinc-700/50 px-3 py-1 rounded-full">
                                Optional
                            </span>
                        </div>

                        <div
                            @dragover.prevent="isDragging = true"
                            @dragleave.prevent="isDragging = false"
                            @drop.prevent="handleDrop($event)"
                            @click="$refs.input.click()"
                            class="relative rounded-xl border-2 transition-all duration-300 cursor-pointer overflow-hidden"
                            :class="isDragging ? 'border-[#FFF200] dark:border-yellow-400 bg-[#FFF200]/10 dark:bg-yellow-400/10 scale-[1.02]' : 'border-dashed border-[#9B9EA4]/30 dark:border-zinc-600 hover:border-[#FFF200] dark:hover:border-yellow-400 hover:bg-[#F8EBD5]/30 dark:hover:bg-zinc-700/30'"
                        >
                            <input x-ref="input" type="file" accept=".pdf" class="hidden" @change="handleFile($event)" />

                            <div x-show="!hasFile" class="p-12 text-center space-y-4">
                                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-[#F8EBD5] dark:bg-zinc-700/50 mx-auto">
                                    <svg class="w-8 h-8 text-[#FFF200] dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-base font-medium text-[#231F20] dark:text-white">
                                        {{ __('Drop your PDF here or click to browse') }}
                                    </p>
                                    <p class="text-sm text-[#9B9EA4] dark:text-zinc-400 mt-1">
                                        {{ __('Maximum file size: 5MB') }}
                                    </p>
                                </div>
                                <div class="flex items-center justify-center space-x-4 text-xs text-[#9B9EA4] dark:text-zinc-400">
                                    <div class="flex items-center space-x-1">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        <span>PDF Only</span>
                                    </div>
                                    <div class="flex items-center space-x-1">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        <span>Secure Upload</span>
                                    </div>
                                    <div class="flex items-center space-x-1">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        <span>Up to 5MB</span>
                                    </div>
                                </div>
                            </div>

                            <!-- File Preview with Progress -->
                            <div x-show="hasFile" class="p-6 space-y-4">
                                <div class="flex items-center space-x-4">
                                    <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-red-100 dark:bg-red-900/20 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-[#231F20] dark:text-white truncate" x-text="fileName"></p>
                                        <p class="text-xs text-[#9B9EA4] dark:text-zinc-400 mt-1">
                                            <span x-show="progress < 100">Uploading...</span>
                                            <span x-show="progress === 100">Ready to submit</span>
                                        </p>
                                    </div>
                                    <button type="button" @click.stop="reset()" class="flex-shrink-0 text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                                
                                <!-- Progress Bar -->
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between text-xs text-[#9B9EA4] dark:text-zinc-400">
                                        <span>Upload Progress</span>
                                        <span x-text="progress + '%'"></span>
                                    </div>
                                    <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-2 overflow-hidden">
                                        <div 
                                            :style="`width: ${progress}%`" 
                                            class="h-2 bg-gradient-to-r from-[#FFF200] to-yellow-300 dark:from-yellow-400 dark:to-yellow-500 transition-all duration-300 rounded-full"
                                            :class="progress === 100 ? 'animate-pulse' : ''"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <p class="text-xs text-[#9B9EA4] dark:text-zinc-400 flex items-start space-x-1" x-text="helperText">
                        </p>
                    </div>

                    <script>
                        function fileUpload() {
                            return {
                                isDragging: false,
                                hasFile: false,
                                progress: 0,
                                fileName: '',
                                helperText: '{{ __('Add diagrams, technical specifications, or any supporting documentation (optional but recommended)') }}',

                                init() {},

                                reset() {
                                    this.isDragging = false;
                                    this.hasFile = false;
                                    this.progress = 0;
                                    this.fileName = '';
                                    this.helperText = '{{ __('Add diagrams, technical specifications, or any supporting documentation (optional but recommended)') }}';
                                },

                                handleDrop(e) {
                                    const dt = e.dataTransfer;
                                    if (!dt || !dt.files || dt.files.length === 0) return;
                                    this.handleFile({ target: { files: dt.files } });
                                    this.isDragging = false;
                                },

                                handleFile(e) {
                                    const file = e.target.files[0];
                                    if (!file) return;

                                    const maxSize = 5 * 1024 * 1024;
                                    if (file.type !== 'application/pdf') {
                                        this.helperText = '❌ Only PDF files are allowed.';
                                        this.reset();
                                        return;
                                    }
                                    if (file.size > maxSize) {
                                        this.helperText = '❌ File is too large. Maximum size is 5MB.';
                                        this.reset();
                                        return;
                                    }

                                    this.hasFile = true;
                                    this.fileName = file.name;
                                    this.helperText = 'Processing file...';

                                    const reader = new FileReader();
                                    reader.onprogress = (evt) => {
                                        if (evt.lengthComputable) {
                                            this.progress = Math.round((evt.loaded / evt.total) * 100);
                                        }
                                    };

                                    reader.onload = () => {
                                        this.progress = 100;
                                        this.helperText = '✅ File ready for submission';
                                    };

                                    reader.onerror = () => {
                                        this.helperText = '❌ Failed to read file. Please try again.';
                                        this.reset();
                                    };

                                    reader.readAsArrayBuffer(file);
                                }
                            }
                        }
                    </script>

                    <!-- Gradient Divider -->
                    <div class="h-px bg-gradient-to-r from-transparent via-[#9B9EA4] dark:via-zinc-600 to-transparent my-8"></div>

                    <!-- Collaboration Settings -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-[#231F20] dark:text-white flex items-center space-x-2">
                            <svg class="w-5 h-5 text-[#FFF200] dark:text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                            </svg>
                            <span>{{ __('Collaboration Options') }}</span>
                        </h3>

                        <div class="bg-[#F8EBD5] dark:bg-zinc-900/50 rounded-xl p-6 space-y-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <flux:checkbox
                                        wire:model.live="collaboration_enabled"
                                        class="text-base"
                                    >
                                    </flux:checkbox>
                                    <span class="font-medium text-[#231F20] dark:text-white">
                                        {{ __('Enable Open Collaboration') }}
                                    </span>
                                </div>
                                <p class="text-xs text-[#9B9EA4] dark:text-zinc-400 ml-6">
                                    {{ __('Allow other users to contribute, comment, and enhance this idea during the review process') }}
                                </p>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <flux:checkbox
                                        wire:model.live="team_effort"
                                        class="text-base"
                                    >
                                    </flux:checkbox>
                                    <span class="font-medium text-[#231F20] dark:text-white">
                                        {{ __('This is a Team Effort') }}
                                    </span>
                                </div>
                                <p class="text-xs text-[#9B9EA4] dark:text-zinc-400 ml-6">
                                    {{ __('Multiple people contributed to developing this idea') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Team Members Section (Conditional) -->
                    @if($team_effort)
                    <div class="border-t-2 border-dashed border-[#9B9EA4]/30 dark:border-zinc-600 pt-6 space-y-6" 
                         x-data="{ showForm: true }"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100">
                        
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-[#231F20] dark:text-white flex items-center space-x-2">
                                <svg class="w-5 h-5 text-[#FFF200] dark:text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                                </svg>
                                <span>{{ __('Team Members') }}</span>
                            </h3>
                            <span class="text-xs font-medium text-[#9B9EA4] dark:text-zinc-400 bg-white dark:bg-zinc-700 px-3 py-1 rounded-full border border-[#9B9EA4]/20 dark:border-zinc-600">
                                {{ count($team_members) }} {{ count($team_members) === 1 ? 'member' : 'members' }}
                            </span>
                        </div>

                        <!-- Existing Team Members List -->
                        @if(count($team_members) > 0)
                        <div class="space-y-3">
                            @foreach($team_members as $index => $member)
                            <div class="flex items-center justify-between bg-white dark:bg-zinc-700/50 p-4 rounded-xl border border-[#9B9EA4]/20 dark:border-zinc-600 hover:shadow-md transition-all duration-200">
                                <div class="flex items-center space-x-4">
                                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-gradient-to-br from-[#FFF200] to-yellow-300 dark:from-yellow-400 dark:to-yellow-500 flex items-center justify-center text-[#231F20] dark:text-zinc-900 font-bold text-sm">
                                        {{ strtoupper(substr($member['name'], 0, 2)) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-[#231F20] dark:text-white truncate">{{ $member['name'] }}</p>
                                        <p class="text-sm text-[#9B9EA4] dark:text-zinc-400 truncate">{{ $member['email'] }}</p>
                                        <p class="text-xs text-[#FFF200] dark:text-yellow-400 font-medium mt-1">{{ $member['role'] }}</p>
                                    </div>
                                </div>
                                <button type="button" 
                                        wire:click="removeTeamMember({{ $index }})"
                                        class="flex-shrink-0 p-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                            @endforeach
                        </div>
                        @endif

                        <!-- Add Team Member Form -->
                        <div class="bg-gradient-to-br from-[#F8EBD5]/50 via-white to-white dark:from-zinc-900/50 dark:via-zinc-800/50 dark:to-zinc-800 p-6 rounded-xl border-2 border-dashed border-[#9B9EA4]/30 dark:border-zinc-600 space-y-4">
                            <div class="flex items-center justify-between">
                                <h4 class="text-base font-semibold text-[#231F20] dark:text-white flex items-center space-x-2">
                                    <svg class="w-5 h-5 text-[#FFF200] dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                    <span>{{ __('Add Team Member') }}</span>
                                </h4>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <flux:input
                                    wire:model="team_member_name"
                                    label="Full Name"
                                    type="text"
                                    placeholder="John Doe"
                                    class="w-full"
                                />

                                <flux:input
                                    wire:model="team_member_email"
                                    label="Email Address"
                                    type="email"
                                    placeholder="john@example.com"
                                    class="w-full"
                                />

                                <flux:input
                                    wire:model="team_member_role"
                                    label="Role/Contribution"
                                    type="text"
                                    placeholder="Co-author, Researcher"
                                    class="w-full"
                                />
                            </div>

                            <flux:button
                                variant="outline"
                                type="button"
                                wire:click="addTeamMember"
                                class="w-full md:w-auto border-2 border-[#FFF200] dark:border-yellow-400 text-[#231F20] dark:text-white hover:bg-[#FFF200]/10 dark:hover:bg-yellow-400/10 transition-all duration-200"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                {{ __('Add Team Member') }}
                            </flux:button>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Submit Section with Enhanced Actions -->
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl border-2 border-[#9B9EA4]/20 dark:border-zinc-700 overflow-hidden">
                <!-- Accent Bar -->
                <div class="h-2 bg-gradient-to-r from-[#FFF200] via-yellow-300 to-[#FFF200] dark:from-yellow-400 dark:via-yellow-500 dark:to-yellow-400"></div>
                
                <div class="p-8">
                    <!-- Summary Stats -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                        <div class="text-center p-4 bg-[#F8EBD5] dark:bg-zinc-900/50 rounded-xl">
                            <div class="text-2xl font-bold text-[#231F20] dark:text-white">
                                {{ str_word_count($idea_title . ' ' . $abstract . ' ' . $problem_statement . ' ' . $proposed_solution) }}
                            </div>
                            <div class="text-xs text-[#9B9EA4] dark:text-zinc-400 mt-1">Total Words</div>
                        </div>
                        <div class="text-center p-4 bg-[#F8EBD5] dark:bg-zinc-900/50 rounded-xl">
                            <div class="text-2xl font-bold text-[#231F20] dark:text-white">
                                {{ $team_effort ? count($team_members) + 1 : 1 }}
                            </div>
                            <div class="text-xs text-[#9B9EA4] dark:text-zinc-400 mt-1">Contributors</div>
                        </div>
                        <div class="text-center p-4 bg-[#F8EBD5] dark:bg-zinc-900/50 rounded-xl">
                            <div class="text-2xl font-bold text-[#231F20] dark:text-white">
                                {{ $original_idea_disclaimer ? '✓' : '–' }}
                            </div>
                            <div class="text-xs text-[#9B9EA4] dark:text-zinc-400 mt-1">Disclaimer</div>
                        </div>
                        <div class="text-center p-4 bg-[#F8EBD5] dark:bg-zinc-900/50 rounded-xl">
                            <div class="text-2xl font-bold text-[#231F20] dark:text-white">
                                {{ $collaboration_enabled ? 'Open' : 'Private' }}
                            </div>
                            <div class="text-xs text-[#9B9EA4] dark:text-zinc-400 mt-1">Collaboration</div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-4">
                        <flux:button
                            icon="bookmark"
                            variant="outline"
                            type="button"
                            wire:click="saveDraft"
                            class="flex-1 justify-center rounded-xl border-2 border-[#9B9EA4] dark:border-zinc-600 hover:border-[#FFF200] dark:hover:border-yellow-400 hover:bg-[#F8EBD5]/30 dark:hover:bg-zinc-700/50 p-2 transition-all duration-200"
                        >
                            {{ __('Save as Draft') }}
                        </flux:button>

                        <flux:button
                            icon="paper-airplane"
                            variant="primary"
                            type="button"
                            wire:click="submitIdea"
                            class="flex-1 justify-center rounded-xl bg-[#FFF200] dark:bg-yellow-400 p-2 text-base font-bold text-[#231F20] dark:text-zinc-900 shadow-xl hover:bg-[#FFF200]/90 dark:hover:bg-yellow-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#FFF200] dark:focus-visible:outline-yellow-400 transition-all duration-200 hover:shadow-2xl hover:scale-[1.02]"
                        >
                            {{ __('Submit Innovation') }}
                        </flux:button>
                    </div>

                    <!-- Helper Note -->
                    <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                        <div class="flex items-start space-x-3">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <div class="flex-1 text-sm">
                                <p class="font-semibold text-blue-900 dark:text-blue-200">
                                    {{ __('What happens next?') }}
                                </p>
                                <p class="text-blue-700 dark:text-blue-300 mt-1">
                                    {{ __('Your submission will be reviewed by our innovation team within 5-7 business days. You\'ll receive email updates on your idea\'s progress and can track it in your dashboard.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Bottom Trust Indicators -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-8">
            <div class="bg-white dark:bg-zinc-800 rounded-xl p-5 border border-[#9B9EA4]/20 dark:border-zinc-700 hover:shadow-lg transition-all duration-300 hover:scale-[1.02]">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-[#FFF200]/10 dark:bg-yellow-400/10 flex items-center justify-center">
                        <svg class="w-6 h-6 text-[#FFF200] dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-[#231F20] dark:text-white">
                            {{ __('IP Protected') }}
                        </p>
                        <p class="text-xs text-[#9B9EA4] dark:text-zinc-400 mt-0.5">
                            {{ __('Your ideas remain yours') }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-xl p-5 border border-[#9B9EA4]/20 dark:border-zinc-700 hover:shadow-lg transition-all duration-300 hover:scale-[1.02]">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-[#FFF200]/10 dark:bg-yellow-400/10 flex items-center justify-center">
                        <svg class="w-6 h-6 text-[#FFF200] dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-[#231F20] dark:text-white">
                            {{ __('Fast Review') }}
                        </p>
                        <p class="text-xs text-[#9B9EA4] dark:text-zinc-400 mt-0.5">
                            {{ __('Response in 5-7 days') }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-xl p-5 border border-[#9B9EA4]/20 dark:border-zinc-700 hover:shadow-lg transition-all duration-300 hover:scale-[1.02]">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-[#FFF200]/10 dark:bg-yellow-400/10 flex items-center justify-center">
                        <svg class="w-6 h-6 text-[#FFF200] dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-[#231F20] dark:text-white">
                            {{ __('Earn Rewards') }}
                        </p>
                        <p class="text-xs text-[#9B9EA4] dark:text-zinc-400 mt-0.5">
                            {{ __('Points & recognition') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Help Section -->
        <div class="bg-gradient-to-br from-[#F8EBD5] to-white dark:from-zinc-800 dark:to-zinc-900 rounded-2xl p-8 border border-[#9B9EA4]/20 dark:border-zinc-700 mt-8">
            <div class="text-center space-y-4">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-white dark:bg-zinc-700 shadow-lg">
                    <svg class="w-7 h-7 text-[#FFF200] dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-[#231F20] dark:text-white">
                        {{ __('Need Help?') }}
                    </h3>
                    <p class="text-sm text-[#9B9EA4] dark:text-zinc-400 mt-2 max-w-2xl mx-auto">
                        {{ __('Our innovation team is here to assist you. Check out our submission guidelines or reach out for personalized support.') }}
                    </p>
                </div>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
                    <flux:button
                        variant="outline"
                        type="button"
                        class="border-2 border-[#9B9EA4] dark:border-zinc-600 hover:border-[#FFF200] dark:hover:border-yellow-400 transition-all duration-200"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        {{ __('View Guidelines') }}
                    </flux:button>
                    <flux:button
                        variant="outline"
                        type="button"
                        class="border-2 border-[#9B9EA4] dark:border-zinc-600 hover:border-[#FFF200] dark:hover:border-yellow-400 transition-all duration-200"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        {{ __('Contact Support') }}
                    </flux:button>
                </div>
            </div>
        </div>
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
        input:focus, textarea:focus, select:focus {
            transform: translateY(-1px);
        }
        
        /* Pulse animation for important indicators */
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.7;
            }
        }
    </style>
</div>