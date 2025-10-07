<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Users Management</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Manage and monitor all users in your system
            </p>
        </div>
        <div class="flex items-center space-x-3">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                Total: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $users->total() }}</span>
            </div>
        </div>
    </div>

    {{-- Table Filters --}}
    <x-table.filters
        :search="$search"
        searchPlaceholder="Search users by name, username, or email..."
        :perPage="$perPage"
        :showBulkActions="count($selectedUsers) > 0"
        :selectedCount="count($selectedUsers)"
    >
        {{-- Status Filter --}}
        <x-slot name="filters">
            <select wire:model.live="status" class="border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-sm bg-white dark:bg-gray-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="">All Statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="banned">Banned</option>
                <option value="disabled">Disabled</option>
            </select>
        </x-slot>

        {{-- Bulk Actions --}}
        <x-slot name="bulkActions">
            <button
                wire:click="exportSelected"
                class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150"
            >
                <flux:icon name="arrow-down-tray" class="h-4 w-4 mr-1.5" />
                Export
            </button>

            <button
                wire:click="deleteSelected"
                wire:confirm="Are you sure you want to delete the selected users? This action cannot be undone."
                class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-white bg-red-600 border border-red-600 rounded-md hover:bg-red-700 transition-colors duration-150"
            >
                <flux:icon name="trash" class="h-4 w-4 mr-1.5" />
                Delete Selected
            </button>
        </x-slot>
    </x-table.filters>

    {{-- Bulk Actions Bar --}}
    <x-table.bulk-actions
        :selectedIds="$selectedUsers"
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
                'confirm' => 'Are you sure you want to delete the selected users? This action cannot be undone.',
                'variant' => 'danger'
            ]
        ]"
    />

    {{-- Main Users Table --}}
    <x-table
        :loading="$this->updatingSearch || $this->updatingStatus || $this->updatingRole"
        :empty="count($users) === 0 && !$this->updatingSearch && !$this->updatingStatus && !$this->updatingRole"
        emptyTitle="No users found"
        emptyDescription="Try adjusting your search criteria or filters to find users."
        emptyIcon="users"
        class="shadow-lg"
    >
        {{-- Table Header --}}
        <x-slot name="head">
            <tr>
                <th class="px-6 py-3 w-12">
                    <flux:checkbox
                        wire:model.live="selectAll"
                        :indeterminate="count($selectedUsers) > 0 && count($selectedUsers) < count($users)"
                    />
                </th>
                <x-table.column
                    sortable
                    sortField="first_name"
                    :currentSort="$sortField"
                    :currentDirection="$sortDirection"
                    width="w-48"
                >
                    Name
                </x-table.column>
                <x-table.column
                    sortable
                    sortField="username"
                    :currentSort="$sortField"
                    :currentDirection="$sortDirection"
                    width="w-32"
                >
                    Username
                </x-table.column>
                <x-table.column
                    sortable
                    sortField="email"
                    :currentSort="$sortField"
                    :currentDirection="$sortDirection"
                    width="w-64"
                >
                    Email
                </x-table.column>
                <x-table.column
                    sortable
                    sortField="account_status"
                    :currentSort="$sortField"
                    :currentDirection="$sortDirection"
                    width="w-24"
                >
                    Status
                </x-table.column>
                <x-table.column
                    sortable
                    sortField="created_at"
                    :currentSort="$sortField"
                    :currentDirection="$sortDirection"
                    align="right"
                    width="w-32"
                >
                    Joined
                </x-table.column>
                <th class="px-6 py-3 text-right w-32">Actions</th>
            </tr>
        </x-slot>

        {{-- Table Body --}}
        <x-slot name="body">
            @foreach($users as $user)
                <x-table.row
                    :selected="in_array($user->id, $selectedUsers)"
                    wire:key="user-{{ $user->id }}"
                >
                    <td class="px-6 py-4">
                        <flux:checkbox
                            wire:model.live="selectedUsers"
                            value="{{ $user->id }}"
                        />
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10">
                                @if($user->first_name && $user->other_names)
                                    <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-semibold text-sm">
                                        {{ substr($user->first_name, 0, 1) . substr($user->other_names, 0, 1) }}
                                    </div>
                                @else
                                    <div class="h-10 w-10 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                                        <flux:icon name="user" class="h-5 w-5 text-gray-600 dark:text-gray-400" />
                                    </div>
                                @endif
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $user->first_name ? ($user->first_name . ' ' . ($user->other_names ?? '')) : ($user->username ?? 'N/A') }}
                                </div>
                                @if($user->staff && $user->staff->job_title)
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $user->staff->job_title }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900 dark:text-gray-100 font-mono">
                            {{ $user->username ?? '—' }}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900 dark:text-gray-100">
                            {{ $user->email }}
                        </div>
                        @if($user->email_verified_at)
                            <div class="text-xs text-green-600 dark:text-green-400 flex items-center mt-1">
                                <flux:icon name="check-circle" class="h-3 w-3 mr-1" />
                                Verified
                            </div>
                        @else
                            <div class="text-xs text-yellow-600 dark:text-yellow-400 flex items-center mt-1">
                                <flux:icon name="exclamation-triangle" class="h-3 w-3 mr-1" />
                                Unverified
                            </div>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @php
                            $statusConfig = match($user->account_status) {
                                'active' => ['color' => 'green', 'icon' => 'check-circle', 'text' => 'Active'],
                                'inactive' => ['color' => 'gray', 'icon' => 'pause-circle', 'text' => 'Inactive'],
                                'banned' => ['color' => 'red', 'icon' => 'x-circle', 'text' => 'Banned'],
                                'disabled' => ['color' => 'yellow', 'icon' => 'exclamation-triangle', 'text' => 'Disabled'],
                                default => ['color' => 'gray', 'icon' => 'question-mark-circle', 'text' => 'Unknown']
                            };
                        @endphp
                        <button
                            wire:click="toggleUserStatus({{ $user->id }})"
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $statusConfig['color'] }}-100 dark:bg-{{ $statusConfig['color'] }}-900 text-{{ $statusConfig['color'] }}-800 dark:text-{{ $statusConfig['color'] }}-200 hover:bg-{{ $statusConfig['color'] }}-200 dark:hover:bg-{{ $statusConfig['color'] }}-800 transition-colors duration-150"
                            wire:confirm="Are you sure you want to {{ $user->account_status === 'active' ? 'deactivate' : 'activate' }} this user?"
                        >
                            <flux:icon name="{{ $statusConfig['icon'] }}" class="h-3 w-3 mr-1" />
                            {{ $statusConfig['text'] }}
                        </button>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-right">
                        <div>{{ $user->created_at->format('M j, Y') }}</div>
                        <div class="text-xs">{{ $user->created_at->format('H:i') }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex items-center justify-end space-x-2">
                            <button
                                wire:click="viewUser({{ $user->id }})"
                                class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 transition-colors duration-150 p-1 rounded"
                                title="View User"
                            >
                                <flux:icon name="eye" class="h-5 w-5" />
                            </button>
                            <button
                                wire:click="editUser({{ $user->id }})"
                                class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 transition-colors duration-150 p-1 rounded"
                                title="Edit User"
                            >
                                <flux:icon name="pencil" class="h-5 w-5" />
                            </button>
                            <button
                                wire:click="deleteUser({{ $user->id }})"
                                wire:confirm="Are you sure you want to delete this user? This action cannot be undone."
                                class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 transition-colors duration-150 p-1 rounded"
                                title="Delete User"
                            >
                                <flux:icon name="trash" class="h-5 w-5" />
                            </button>
                        </div>
                    </td>
                </x-table.row>
            @endforeach
        </x-slot>
    </x-table>

    {{-- Table Pagination --}}
    <x-table.pagination :paginator="$users" />
</div>