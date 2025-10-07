<x-layouts.app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
        {{-- Welcome Section --}}
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl p-6 text-white">
            <h1 class="text-2xl font-bold mb-2">Welcome back, {{ auth()->user()->first_name ?? auth()->user()->username }}!</h1>
            <p class="text-blue-100">Here's what's happening with your users today.</p>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center">
                            <flux:icon name="users" class="h-5 w-5 text-white" />
                        </div>
                    </div>
                    <div class="ml-4">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Total Users</dt>
                        <dd class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ \App\Models\User::count() }}</dd>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-500 rounded-lg flex items-center justify-center">
                            <flux:icon name="check-circle" class="h-5 w-5 text-white" />
                        </div>
                    </div>
                    <div class="ml-4">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Active Users</dt>
                        <dd class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ \App\Models\User::where('account_status', 'active')->count() }}</dd>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-500 rounded-lg flex items-center justify-center">
                            <flux:icon name="clock" class="h-5 w-5 text-white" />
                        </div>
                    </div>
                    <div class="ml-4">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Pending Verification</dt>
                        <dd class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ \App\Models\User::whereNull('email_verified_at')->count() }}</dd>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-red-500 rounded-lg flex items-center justify-center">
                            <flux:icon name="x-circle" class="h-5 w-5 text-white" />
                        </div>
                    </div>
                    <div class="ml-4">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Banned Users</dt>
                        <dd class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ \App\Models\User::where('account_status', 'banned')->count() }}</dd>
                    </div>
                </div>
            </div>
        </div>

        {{-- Users Management Table --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <livewire:dashboard-users-table />
        </div>
    </div>
</x-layouts.app>
