{{--
    Card Components Usage Examples
    ==============================

    This file demonstrates how to use the various card components in the cards/ directory.
    These components provide a flexible card-based layout system similar to the table components.

    Available Components:
    - card.blade.php: Main card container with hover/selection states
    - card-grid.blade.php: Responsive grid container for multiple cards
    - card-header.blade.php: Card header with title, subtitle, avatar, badge, meta, and actions
    - card-body.blade.php: Main content area of the card
    - card-footer.blade.php: Footer with actions and meta information
    - card-stats.blade.php: Statistics/metrics display within cards
    - card-actions.blade.php: Action buttons component

    Plus shared components from the table folder:
    - bulk-actions.blade.php: Bulk action controls
    - delete-modal.blade.php: Confirmation modal for deletions
    - empty-state.blade.php: Empty state display
    - filters.blade.php: Filter controls
    - pagination.blade.php: Pagination controls
--}}

{{-- Example 1: Basic User Card --}}
<x-cards.card>
    <x-cards.card-header
        title="John Doe"
        subtitle="Software Engineer"
        :avatar="['src' => 'https://example.com/avatar.jpg', 'alt' => 'John Doe']"
        :badge="['text' => 'Active', 'variant' => 'success']"
        :meta="[
            ['icon' => 'mail', 'text' => 'john.doe@kenha.co.ke'],
            ['icon' => 'phone', 'text' => '+254 700 123 456']
        ]"
        :actions="[
            ['text' => 'Edit', 'variant' => 'ghost', 'wireClick' => 'editUser(' . $user->id . ')'],
            ['text' => 'Delete', 'variant' => 'danger', 'wireClick' => 'deleteUser(' . $user->id . ')', 'confirm' => 'Are you sure?']
        ]"
    />

    <x-cards.card-body>
        <p>John is a senior software engineer with 5+ years of experience in Laravel development. He specializes in building scalable web applications and has contributed to several key projects at KeNHA.</p>
    </x-cards.card-body>

    <x-cards.card-footer
        :meta="[
            ['icon' => 'calendar', 'text' => 'Joined 2 years ago'],
            ['icon' => 'map-pin', 'text' => 'Nairobi, Kenya']
        ]"
        :actions="[
            ['text' => 'View Profile', 'variant' => 'primary', 'href' => route('users.show', $user->id)],
            ['text' => 'Message', 'variant' => 'secondary', 'wireClick' => 'messageUser(' . $user->id . ')']
        ]"
    />
</x-cards.card>

{{-- Example 2: Statistics Card --}}
<x-cards.card>
    <x-cards.card-header
        title="System Overview"
        :actions="[
            ['text' => 'Refresh', 'variant' => 'ghost', 'wireClick' => 'refreshStats'],
            ['text' => 'Export', 'variant' => 'secondary', 'href' => route('reports.export')]
        ]"
    />

    <x-cards.card-stats
        :columns="2"
        :stats="[
            [
                'value' => '1,234',
                'label' => 'Total Users',
                'icon' => 'users',
                'change' => '+12%',
                'changeType' => 'positive',
                'variant' => 'primary'
            ],
            [
                'value' => '89',
                'label' => 'Active Sessions',
                'icon' => 'activity',
                'change' => '+5%',
                'changeType' => 'positive',
                'variant' => 'success'
            ],
            [
                'value' => '45',
                'label' => 'Pending Approvals',
                'icon' => 'clock',
                'change' => '-8%',
                'changeType' => 'positive',
                'variant' => 'warning'
            ],
            [
                'value' => '3',
                'label' => 'System Alerts',
                'icon' => 'alert-triangle',
                'change' => '+1',
                'changeType' => 'negative',
                'variant' => 'danger'
            ]
        ]"
    />
</x-cards.card>

{{-- Example 3: Project Card with Custom Content --}}
<x-cards.card>
    <x-cards.card-header
        title="Road Infrastructure Project"
        subtitle="Mombasa-Nairobi Highway Expansion"
        :badge="['text' => 'In Progress', 'variant' => 'warning']"
        :meta="[
            ['icon' => 'calendar', 'text' => 'Started Jan 2024'],
            ['icon' => 'dollar-sign', 'text' => 'Budget: KES 50B']
        ]"
        :actions="[
            ['text' => 'View Details', 'variant' => 'primary', 'href' => route('projects.show', $project->id)],
            ['text' => 'Edit', 'variant' => 'ghost', 'wireClick' => 'editProject(' . $project->id . ')']
        ]"
    />

    <x-cards.card-body>
        <div class="space-y-3">
            <div class="flex justify-between text-sm">
                <span class="text-[#9B9EA4] dark:text-zinc-400">Progress</span>
                <span class="font-medium">75%</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-zinc-700 rounded-full h-2">
                <div class="bg-[#2563EB] h-2 rounded-full" style="width: 75%"></div>
            </div>
            <p class="text-sm text-[#231F20] dark:text-zinc-200">
                This major infrastructure project aims to improve transportation connectivity between Mombasa and Nairobi, reducing travel time and boosting economic growth.
            </p>
        </div>
    </x-cards.card-body>

    <x-cards.card-footer
        :meta="[
            ['icon' => 'users', 'text' => '12 Team Members'],
            ['icon' => 'check-circle', 'text' => '8 Milestones Completed']
        ]"
        :actions="[
            ['text' => 'Add Update', 'variant' => 'secondary', 'wireClick' => 'addProjectUpdate(' . $project->id . ')'],
            ['text' => 'Archive', 'variant' => 'danger', 'wireClick' => 'archiveProject(' . $project->id . ')', 'confirm' => 'Archive this project?']
        ]"
    />
</x-cards.card>

{{-- Example 4: Card Grid with Multiple Cards --}}
<x-cards.card-grid
    :loading="$loading"
    :empty="!$users->count()"
    empty-title="No users found"
    empty-description="Get started by adding your first user."
    empty-action="Add User"
    :empty-action-wire-click="'openCreateModal'"
>
    @foreach($users as $user)
        <x-cards.card
            :wire:key="'user-' . $user->id"
            :selected="$selectedUsers->contains($user->id)"
            wire:click="toggleUserSelection({{ $user->id }})"
        >
            <x-cards.card-header
                :title="$user->name"
                :subtitle="$user->email"
                :badge="[
                    'text' => $user->account_status,
                    'variant' => $user->account_status === 'active' ? 'success' : 'warning'
                ]"
                :meta="[
                    ['icon' => 'calendar', 'text' => $user->created_at->format('M j, Y')],
                    ['icon' => 'star', 'text' => $user->points . ' points']
                ]"
            />

            <x-cards.card-body>
                @if($user->staff)
                    <div class="text-sm text-[#9B9EA4] dark:text-zinc-400">
                        <p><strong>Department:</strong> {{ $user->staff->department->name ?? 'N/A' }}</p>
                        <p><strong>Position:</strong> {{ $user->staff->job_title ?? 'N/A' }}</p>
                    </div>
                @else
                    <p class="text-sm text-[#9B9EA4] dark:text-zinc-400">Regular user account</p>
                @endif
            </x-cards.card-body>

            <x-cards.card-footer
                :actions="[
                    ['text' => 'Edit', 'variant' => 'ghost', 'wireClick' => 'editUser(' . $user->id . ')'],
                    ['text' => 'View', 'variant' => 'secondary', 'href' => route('users.show', $user->id)]
                ]"
            />
        </x-cards.card>
    @endforeach
</x-cards.card-grid>

{{-- Example 5: Using with Filters and Pagination --}}
<div class="space-y-6">
    {{-- Filters --}}
    <x-cards.filters
        :filters="$filters"
        :available-filters="$availableFilters"
        wire:model="filters"
    />

    {{-- Card Grid --}}
    <x-cards.card-grid
        :loading="$loading"
        :empty="!$items->count()"
        empty-title="No items found"
        empty-description="Try adjusting your filters or create a new item."
    >
        {{-- Cards go here --}}
    </x-cards.card-grid>

    {{-- Pagination --}}
    <x-cards.pagination
        :paginator="$items"
        :per-page-options="[10, 25, 50, 100]"
    />
</div>

{{-- Example 6: Bulk Actions with Cards --}}
@if($selectedUsers->count() > 0)
    <x-cards.bulk-actions
        :selected-count="$selectedUsers->count()"
        :actions="[
            ['text' => 'Activate', 'variant' => 'success', 'wireClick' => 'bulkActivate'],
            ['text' => 'Deactivate', 'variant' => 'warning', 'wireClick' => 'bulkDeactivate'],
            ['text' => 'Delete', 'variant' => 'danger', 'wireClick' => 'bulkDelete', 'confirm' => 'Are you sure you want to delete ' . $selectedUsers->count() . ' users?']
        ]"
    />
@endif

<x-cards.card-grid
    :loading="$loading"
    :empty="!$users->count()"
>
    @foreach($users as $user)
        <x-cards.card
            :wire:key="'user-' . $user->id"
            :selected="$selectedUsers->contains($user->id)"
            wire:click="toggleUserSelection({{ $user->id }})"
        >
            {{-- Card content --}}
        </x-cards.card>
    @endforeach
</x-cards.card-grid>