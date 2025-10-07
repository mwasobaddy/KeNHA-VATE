<div>
    {{-- Table Filters --}}
    <x-table.filters
        :search="$search"
        searchPlaceholder="Search users by name or email..."
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
                wire:confirm="Are you sure you want to delete the selected users?"
                class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-white bg-red-600 border border-red-600 rounded-md hover:bg-red-700 transition-colors duration-150"
            >
                <flux:icon name="trash" class="h-4 w-4 mr-1.5" />
                Delete
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
                'confirm' => 'Are you sure you want to delete the selected users?',
                'variant' => 'danger'
            ]
        ]"
    />

    {{-- Main Table --}}
    <x-table
        :loading="$this->updatingSearch || $this->updatingStatus || $this->updatingRole"
        :empty="count($users) === 0 && !$this->updatingSearch && !$this->updatingStatus && !$this->updatingRole"
        emptyTitle="No users found"
        emptyDescription="Try adjusting your search or filters to find what you're looking for."
    >
        {{-- Table Header --}}
        <x-slot name="head">
            <tr>
                <th class="px-6 py-3">
                    <flux:checkbox
                        wire:model.live="selectAll"
                        :indeterminate="count($selectedUsers) > 0 && count($selectedUsers) < count($users)"
                    />
                </th>
                <x-table.column
                    sortable
                    sortField="name"
                    :currentSort="$sortField"
                    :currentDirection="$sortDirection"
                >
                    Name
                </x-table.column>
                <x-table.column
                    sortable
                    sortField="email"
                    :currentSort="$sortField"
                    :currentDirection="$sortDirection"
                >
                    Email
                </x-table.column>
                <x-table.column
                    sortable
                    sortField="account_status"
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
                                <div class="h-10 w-10 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ substr($user->name, 0, 2) }}
                                    </span>
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $user->name }}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                        {{ $user->email }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @php
                            $statusColor = match($user->account_status) {
                                'active' => 'green',
                                'inactive' => 'gray',
                                'banned' => 'red',
                                default => 'gray'
                            };
                            $statusText = ucfirst($user->account_status ?? 'unknown');
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $statusColor }}-100 dark:bg-{{ $statusColor }}-900 text-{{ $statusColor }}-800 dark:text-{{ $statusColor }}-200">
                            {{ $statusText }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-right">
                        {{ $user->created_at->format('M j, Y') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex items-center justify-end space-x-2">
                            <button
                                wire:click="viewUser({{ $user->id }})"
                                class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 transition-colors duration-150"
                            >
                                <flux:icon name="eye" class="h-5 w-5" />
                            </button>
                            <button
                                wire:click="editUser({{ $user->id }})"
                                class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 transition-colors duration-150"
                            >
                                <flux:icon name="pencil" class="h-5 w-5" />
                            </button>
                            <button
                                wire:click="deleteUser({{ $user->id }})"
                                wire:confirm="Are you sure you want to delete this user?"
                                class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 transition-colors duration-150"
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