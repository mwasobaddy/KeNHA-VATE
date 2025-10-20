<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\AuditLog;
use App\Models\PointTransaction;
use App\Models\IdeaRevision;
use App\Models\IdeaCollaborationRequest;

new #[Layout('components.layouts.app')] class extends Component {
    public $filter = 'all'; // all, revisions, requests, points, audits
    public $timeframe = '30'; // 7, 30, 90 days

    /**
     * Get filtered activity
     */
    public function getFilteredActivity()
    {
        $activities = collect();
        $startDate = now()->subDays((int) $this->timeframe);

        // Get user's collaborative ideas for filtering
        $userIdeaIds = Auth::user()->ideas()->pluck('id')->toArray();

        switch ($this->filter) {
            case 'revisions':
                // Revisions on user's ideas or by user as collaborator
                $revisionActivity = IdeaRevision::where(function ($query) use ($userIdeaIds) {
                    $query->whereIn('idea_id', $userIdeaIds)
                          ->orWhere('created_by', Auth::id());
                })
                ->where('created_at', '>=', $startDate)
                ->with(['idea', 'user'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($revision) {
                    return [
                        'id' => 'revision_' . $revision->id,
                        'type' => 'revision',
                        'title' => 'Revision ' . $revision->revision_number,
                        'description' => $revision->change_summary ?: 'Revision created',
                        'idea' => $revision->idea,
                        'user' => $revision->user,
                        'created_at' => $revision->created_at,
                        'metadata' => [
                            'revision_type' => $revision->revision_type,
                            'status' => $revision->status,
                        ]
                    ];
                });
                $activities = $activities->merge($revisionActivity);
                break;

            case 'requests':
                // Collaboration requests sent or received
                $requestActivity = IdeaCollaborationRequest::where(function ($query) use ($userIdeaIds) {
                    $query->where('requester_id', Auth::id())
                          ->orWhereIn('idea_id', $userIdeaIds);
                })
                ->where('created_at', '>=', $startDate)
                ->with(['requester', 'idea.user'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($request) {
                    $isSender = $request->requester_id === Auth::id();
                    return [
                        'id' => 'request_' . $request->id,
                        'type' => 'request',
                        'title' => $isSender ? 'Collaboration request sent' : 'Collaboration request received',
                        'description' => $isSender
                            ? 'Requested to collaborate on "' . $request->idea->idea_title . '"'
                            : 'Received collaboration request for "' . $request->idea->idea_title . '" from ' . $request->requester->name,
                        'idea' => $request->idea,
                        'user' => $isSender ? $request->idea->user : $request->requester,
                        'created_at' => $request->created_at,
                        'metadata' => [
                            'status' => $request->status,
                            'is_sender' => $isSender,
                        ]
                    ];
                });
                $activities = $activities->merge($requestActivity);
                break;

            case 'points':
                // Point transactions related to collaboration
                $pointActivity = PointTransaction::where('user_id', Auth::id())
                    ->where('created_at', '>=', $startDate)
                    ->where('description', 'like', '%collaborat%')
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->map(function ($transaction) {
                        return [
                            'id' => 'points_' . $transaction->id,
                            'type' => 'points',
                            'title' => $transaction->points > 0 ? 'Points earned' : 'Points spent',
                            'description' => $transaction->description,
                            'idea' => null,
                            'user' => Auth::user(),
                            'created_at' => $transaction->created_at,
                            'metadata' => [
                                'points' => $transaction->points,
                                'transaction_type' => $transaction->transaction_type,
                            ]
                        ];
                    });
                $activities = $activities->merge($pointActivity);
                break;

            case 'audits':
                // Audit logs for collaboration activities
                $auditActivity = AuditLog::where('user_id', Auth::id())
                    ->where('created_at', '>=', $startDate)
                    ->where('event_type', 'like', '%collaborat%')
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->map(function ($audit) {
                        return [
                            'id' => 'audit_' . $audit->id,
                            'type' => 'audit',
                            'title' => 'Collaboration activity',
                            'description' => $audit->event_type,
                            'idea' => null,
                            'user' => Auth::user(),
                            'created_at' => $audit->created_at,
                            'metadata' => $audit->metadata,
                        ];
                    });
                $activities = $activities->merge($auditActivity);
                break;

            default: // all
                // Combine all activity types
                $revisionActivity = IdeaRevision::where(function ($query) use ($userIdeaIds) {
                    $query->whereIn('idea_id', $userIdeaIds)
                          ->orWhere('created_by', Auth::id());
                })
                ->where('created_at', '>=', $startDate)
                ->with(['idea', 'user'])
                ->get()
                ->map(function ($revision) {
                    return [
                        'id' => 'revision_' . $revision->id,
                        'type' => 'revision',
                        'title' => 'Revision ' . $revision->revision_number,
                        'description' => $revision->change_summary ?: 'Revision created',
                        'idea' => $revision->idea,
                        'user' => $revision->user,
                        'created_at' => $revision->created_at,
                        'metadata' => [
                            'revision_type' => $revision->revision_type,
                            'status' => $revision->status,
                        ]
                    ];
                });

                $requestActivity = IdeaCollaborationRequest::where(function ($query) use ($userIdeaIds) {
                    $query->where('requester_id', Auth::id())
                          ->orWhereIn('idea_id', $userIdeaIds);
                })
                ->where('created_at', '>=', $startDate)
                ->with(['requester', 'idea.user'])
                ->get()
                ->map(function ($request) {
                    $isSender = $request->requester_id === Auth::id();
                    return [
                        'id' => 'request_' . $request->id,
                        'type' => 'request',
                        'title' => $isSender ? 'Collaboration request sent' : 'Collaboration request received',
                        'description' => $isSender
                            ? 'Requested to collaborate on "' . $request->idea->idea_title . '"'
                            : 'Received collaboration request for "' . $request->idea->idea_title . '" from ' . $request->requester->name,
                        'idea' => $request->idea,
                        'user' => $isSender ? $request->idea->user : $request->requester,
                        'created_at' => $request->created_at,
                        'metadata' => [
                            'status' => $request->status,
                            'is_sender' => $isSender,
                        ]
                    ];
                });

                $pointActivity = PointTransaction::where('user_id', Auth::id())
                    ->where('created_at', '>=', $startDate)
                    ->where('description', 'like', '%collaborat%')
                    ->get()
                    ->map(function ($transaction) {
                        return [
                            'id' => 'points_' . $transaction->id,
                            'type' => 'points',
                            'title' => $transaction->points > 0 ? 'Points earned' : 'Points spent',
                            'description' => $transaction->description,
                            'idea' => null,
                            'user' => Auth::user(),
                            'created_at' => $transaction->created_at,
                            'metadata' => [
                                'points' => $transaction->points,
                                'transaction_type' => $transaction->transaction_type,
                            ]
                        ];
                    });

                $activities = $activities->merge($revisionActivity)
                                       ->merge($requestActivity)
                                       ->merge($pointActivity);
                break;
        }

        return $activities->sortByDesc('created_at')->take(100);
    }

    /**
     * Get activity type icon
     */
    public function getActivityIcon($type)
    {
        return match ($type) {
            'revision' => 'document-text',
            'request' => 'user-plus',
            'points' => 'star',
            'audit' => 'shield-exclamation',
            default => 'circle-stack',
        };
    }

    /**
     * Get activity type color
     */
    public function getActivityColor($type)
    {
        return match ($type) {
            'revision' => 'blue',
            'request' => 'purple',
            'points' => 'yellow',
            'audit' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get filter options
     */
    public function getFilterOptions()
    {
        return [
            'all' => 'All Activity',
            'revisions' => 'Revisions',
            'requests' => 'Requests',
            'points' => 'Points',
            'audits' => 'Audit Logs',
        ];
    }

    /**
     * Get timeframe options
     */
    public function getTimeframeOptions()
    {
        return [
            '7' => 'Last 7 days',
            '30' => 'Last 30 days',
            '90' => 'Last 90 days',
        ];
    }
};
?>

<div class="min-h-screen bg-gradient-to-br from-[#F8EBD5]/20 via-white to-[#F8EBD5] dark:from-zinc-900/20 dark:via-zinc-800 dark:to-zinc-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-[#231F20] dark:text-white">
                        {{ __('Collaboration Activity') }}
                    </h1>
                    <p class="mt-2 text-[#9B9EA4] dark:text-zinc-400">
                        {{ __('Track all your collaboration activities and history') }}
                    </p>
                </div>
                <div class="flex gap-3">
                    <flux:button
                        icon="arrow-left"
                        wire:navigate
                        href="{{ route('ideas.collaboration.dashboard') }}"
                        variant="primary"
                        class="bg-[#FFF200] hover:bg-yellow-400 text-[#231F20] dark:bg-yellow-500 dark:hover:bg-yellow-600"
                    >
                        {{ __('Back to Dashboard') }}
                    </flux:button>
                    <flux:button
                        icon="arrow-down-tray"
                        onclick="window.print()"
                        variant="primary"
                        color="blue"
                    >
                        {{ __('Export Activity') }}
                    </flux:button>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 p-6 mb-6">
            <div class="flex flex-col sm:flex-row gap-4">
                <!-- Activity Type Filter -->
                <div class="flex-1">
                    <label for="filter" class="block text-sm font-medium text-[#231F20] dark:text-white mb-2">
                        {{ __('Activity Type') }}
                    </label>
                    <flux:select wire:model.live="filter" id="filter" class="w-full">
                        @foreach($this->getFilterOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                </div>

                <!-- Timeframe Filter -->
                <div class="sm:w-48">
                    <label for="timeframe" class="block text-sm font-medium text-[#231F20] dark:text-white mb-2">
                        {{ __('Time Period') }}
                    </label>
                    <flux:select wire:model.live="timeframe" id="timeframe" class="w-full">
                        @foreach($this->getTimeframeOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                </div>
            </div>
        </div>

        <!-- Activity Feed -->
        <div class="space-y-4">
            @forelse($this->getFilteredActivity() as $activity)
                <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 p-6">
                    <div class="flex items-start gap-4">
                        <!-- Activity Icon -->
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 rounded-xl bg-{{ $this->getActivityColor($activity['type']) }}-100 dark:bg-{{ $this->getActivityColor($activity['type']) }}-900/20 flex items-center justify-center">
                                <flux:icon name="{{ $this->getActivityIcon($activity['type']) }}" class="w-6 h-6 text-{{ $this->getActivityColor($activity['type']) }}-600 dark:text-{{ $this->getActivityColor($activity['type']) }}-400" />
                            </div>
                        </div>

                        <!-- Activity Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <h3 class="font-semibold text-[#231F20] dark:text-white">
                                        {{ $activity['title'] }}
                                    </h3>
                                    <p class="text-[#9B9EA4] dark:text-zinc-400 mt-1">
                                        {{ $activity['description'] }}
                                    </p>

                                    <!-- Idea Link -->
                                    @if($activity['idea'])
                                        <p class="text-sm text-[#9B9EA4] dark:text-zinc-400 mt-2">
                                            {{ __('Idea:') }}
                                            <a href="{{ route('ideas.show', $activity['idea']->slug) }}" class="text-[#FFF200] hover:text-yellow-400 font-medium">
                                                {{ $activity['idea']->idea_title }}
                                            </a>
                                        </p>
                                    @endif

                                    <!-- Metadata -->
                                    @if(isset($activity['metadata']))
                                        <div class="flex flex-wrap gap-2 mt-3">
                                            @if(isset($activity['metadata']['revision_type']))
                                                <x-revision-badge :status="$activity['metadata']['status']" :type="$activity['metadata']['revision_type']" size="xs" />
                                            @endif

                                            @if(isset($activity['metadata']['status']) && $activity['type'] === 'request')
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                                    @if($activity['metadata']['status'] === 'pending') bg-yellow-100 text-yellow-800
                                                    @elseif($activity['metadata']['status'] === 'approved') bg-green-100 text-green-800
                                                    @elseif($activity['metadata']['status'] === 'rejected') bg-red-100 text-red-800
                                                    @else bg-gray-100 text-gray-800 @endif">
                                                    {{ ucfirst($activity['metadata']['status']) }}
                                                </span>
                                            @endif

                                            @if(isset($activity['metadata']['points']))
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                                    @if($activity['metadata']['points'] > 0) bg-green-100 text-green-800
                                                    @else bg-red-100 text-red-800 @endif">
                                                    <flux:icon name="star" class="w-3 h-3 mr-1" />
                                                    {{ $activity['metadata']['points'] > 0 ? '+' : '' }}{{ $activity['metadata']['points'] }} points
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                <!-- Timestamp -->
                                <div class="flex-shrink-0 text-right">
                                    <time class="text-sm text-[#9B9EA4] dark:text-zinc-400" datetime="{{ $activity['created_at']->toISOString() }}">
                                        {{ $activity['created_at']->format('M j, Y') }}
                                    </time>
                                    <p class="text-xs text-[#9B9EA4] dark:text-zinc-400">
                                        {{ $activity['created_at']->format('g:i A') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-12">
                    <flux:icon name="document" class="w-16 h-16 text-[#9B9EA4] dark:text-zinc-400 mx-auto mb-4" />
                    <h3 class="text-lg font-semibold text-[#231F20] dark:text-white mb-2">
                        {{ __('No Activity Found') }}
                    </h3>
                    <p class="text-[#9B9EA4] dark:text-zinc-400 mb-4">
                        @if($this->filter === 'all')
                            {{ __('You don\'t have any collaboration activity in the selected time period.') }}
                        @else
                            {{ __('No') }} {{ strtolower($this->getFilterOptions()[$this->filter]) }} {{ __('activity found in the selected time period.') }}
                        @endif
                    </p>
                    @if($this->timeframe !== '90')
                        <flux:button
                            wire:click="$set('timeframe', '90')"
                            variant="primary"
                            class="bg-[#FFF200] hover:bg-yellow-400 text-[#231F20] dark:bg-yellow-500 dark:hover:bg-yellow-600"
                        >
                            {{ __('View Last 90 Days') }}
                        </flux:button>
                    @endif
                </div>
            @endforelse
        </div>

        <!-- Activity Summary -->
        @if($this->getFilteredActivity()->isNotEmpty())
            <div class="mt-8 bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 p-6">
                <h3 class="text-lg font-semibold text-[#231F20] dark:text-white mb-4">{{ __('Activity Summary') }}</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                            {{ $this->getFilteredActivity()->where('type', 'revision')->count() }}
                        </div>
                        <div class="text-sm text-[#9B9EA4] dark:text-zinc-400">{{ __('Revisions') }}</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                            {{ $this->getFilteredActivity()->where('type', 'request')->count() }}
                        </div>
                        <div class="text-sm text-[#9B9EA4] dark:text-zinc-400">{{ __('Requests') }}</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                            {{ $this->getFilteredActivity()->where('type', 'points')->count() }}
                        </div>
                        <div class="text-sm text-[#9B9EA4] dark:text-zinc-400">{{ __('Point Transactions') }}</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-600 dark:text-gray-400">
                            {{ $this->getFilteredActivity()->where('type', 'audit')->count() }}
                        </div>
                        <div class="text-sm text-[#9B9EA4] dark:text-zinc-400">{{ __('Audit Events') }}</div>
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>