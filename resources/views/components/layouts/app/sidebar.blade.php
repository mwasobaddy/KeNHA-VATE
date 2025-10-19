<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar stashable sticky class="bg-zinc-50/20 dark:bg-zinc-900/20 w-full backdrop-blur-sm !p-0">
            <div class="border-e border-zinc-200 dark:border-zinc-700 w-fit lg:w-64 h-screen p-4 bg-zinc-50 dark:bg-zinc-900 flex flex-col backdrop-blur-2xl">
                <flux:sidebar.toggle class="lg:hidden left-[86%]" icon="x-mark" />
                <a href="{{ route('dashboard') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
                    <x-app-logo />
                </a>
                <nav class="flex-1 px-4 py-6 overflow-y-auto text-sm">
                    <ul class="space-y-2">
                        <li>
                            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full font-semibold {{ request()->routeIs('dashboard') ? 'bg-[#FFF200] dark:bg-yellow-400 text-[#231F20] dark:text-zinc-900' : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }}" wire:navigate>
                                <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2 {{ request()->routeIs('dashboard') ? 'bg-white dark:bg-zinc-900' : 'dark:bg-zinc-500' }}">
                                    <flux:icon name="home" variant="solid" class="w-4 h-4 text-zinc-500 dark:text-zinc-200" />
                                </span>
                                <span>{{ __('Dashboard') }}</span>
                            </a>
                        </li>

                        <!-- Notifications Link -->
                        <li>
                            <a href="{{ route('notifications.index') }}" class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full font-semibold {{ request()->routeIs('notifications.*') ? 'bg-[#FFF200] dark:bg-yellow-400 text-[#231F20] dark:text-zinc-900' : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} relative" wire:navigate>
                                <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2 {{ request()->routeIs('notifications.*') ? 'bg-white dark:bg-zinc-900' : 'dark:bg-zinc-500' }}">
                                    <flux:icon name="bell" variant="solid" class="w-4 h-4 text-zinc-500 dark:text-zinc-200" />
                                </span>
                                <span>{{ __('Notifications') }}</span>
                                @if(auth()->user()->unreadNotifications()->count() > 0)
                                    <span class="absolute -top-1 -right-1 h-5 w-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center">
                                        {{ auth()->user()->unreadNotifications()->count() }}
                                    </span>
                                @endif
                            </a>
                        </li>

                        <!-- Ideas Link -->
                        <li x-data="{ open: {{ (request()->routeIs('ideas.*')) ? 'true' : 'false' }} }">
                            <div class="flex flex-col">
                                <button type="button"
                                    @click="open = !open"
                                    class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full font-semibold
                                    {{ request()->routeIs('ideas.*')
                                        ? 'bg-[#FFF200] dark:bg-yellow-400 text-[#231F20] dark:text-zinc-900'
                                        : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }}">
                                    <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2
                                        {{ request()->routeIs('ideas.*')
                                            ? 'bg-white dark:bg-zinc-900' : 'dark:bg-zinc-500' }}">
                                        <flux:icon name="light-bulb" variant="solid" class="w-4 h-4 text-zinc-500 dark:text-zinc-200" />
                                    </span>
                                    <span class="w-[60%] text-start">{{ __('Ideas') }}</span>
                                    <svg :class="{ 'rotate-180': open }" class="w-4 h-4 text-zinc-400 transition-transform duration-300 ease-in-out" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M19 9l-7 7-7-7" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                                <ul x-show="open"
                                    x-transition:enter="transition ease-out duration-300"
                                    x-transition:enter-start="opacity-0 transform -translate-y-2 scale-95"
                                    x-transition:enter-end="opacity-100 transform translate-y-0 scale-100"
                                    x-transition:leave="transition ease-in duration-200"
                                    x-transition:leave-start="opacity-100 transform translate-y-0 scale-100"
                                    x-transition:leave-end="opacity-0 transform -translate-y-2 scale-95"
                                    class="pl-8 mt-2 overflow-hidden">
                                    <li>
                                        <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('ideas.submit') || request()->routeIs('ideas.edit_draft.*') ? 'border-[#FFF200] dark:border-yellow-400 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                            <svg class="w-2 h-2 mr-2 {{ request()->routeIs('ideas.submit') || request()->routeIs('ideas.edit_draft.*') ? 'fill-current text-[#FFF200] dark:text-yellow-400' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                                <circle cx="12" cy="12" r="10"/>
                                            </svg>
                                            <a href="{{ route('ideas.submit') }}" wire:navigate>
                                                {{ __('Submit Idea') }}
                                            </a>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('ideas.table') ? 'border-[#FFF200] dark:border-yellow-400 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                            <svg class="w-2 h-2 mr-2 {{ request()->routeIs('ideas.table') ? 'fill-current text-[#FFF200] dark:text-yellow-400' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                                <circle cx="12" cy="12" r="10"/>
                                            </svg>
                                            <a href="{{ route('ideas.table') }}" wire:navigate>
                                                {{ __('My Ideas') }}
                                            </a>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('ideas.public') ? 'border-[#FFF200] dark:border-yellow-400 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                            <svg class="w-2 h-2 mr-2 {{ request()->routeIs('ideas.public') ? 'fill-current text-[#FFF200] dark:text-yellow-400' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                                <circle cx="12" cy="12" r="10"/>
                                            </svg>
                                            <a href="{{ route('ideas.public') }}" wire:navigate>
                                                {{ __('Public Ideas') }}
                                            </a>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </li>

                        @if (Auth::user()->can('manage_employee') || Auth::user()->can('create_employee'))
                        <li x-data="{ open: {{ (request()->routeIs('employee.*')) ? 'true' : 'false' }} }">
                            <div class="flex flex-col">
                                <button type="button"
                                    @click="open = !open"
                                    class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full font-semibold
                                    {{ request()->routeIs('employee.*')
                                        ? 'bg-[#FFF200] dark:bg-yellow-400 text-[#231F20] dark:text-zinc-900'
                                        : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }}">
                                    <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2
                                        {{ request()->routeIs('employee.*')
                                            ? 'bg-white dark:bg-zinc-900' : 'dark:bg-zinc-500' }}">
                                        <flux:icon name="users" variant="solid" class="w-4 h-4 text-zinc-500 dark:text-zinc-200" />
                                    </span>
                                    <span class="w-[60%] text-start">{{ __('Employee') }}</span>
                                    <svg :class="{ 'rotate-180': open }" class="w-4 h-4 text-zinc-400 transition-transform duration-300 ease-in-out" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M19 9l-7 7-7-7" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                                <ul x-show="open" 
                                    x-transition:enter="transition ease-out duration-300"
                                    x-transition:enter-start="opacity-0 transform -translate-y-2 scale-95"
                                    x-transition:enter-end="opacity-100 transform translate-y-0 scale-100"
                                    x-transition:leave="transition ease-in duration-200"
                                    x-transition:leave-start="opacity-100 transform translate-y-0 scale-100"
                                    x-transition:leave-end="opacity-0 transform -translate-y-2 scale-95"
                                    class="pl-8 mt-2 overflow-hidden" class="pl-8 mt-2">
                                    @can('manage_employee')
                                    <li>
                                        <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('employee.index') ? 'border-[#FFF200] dark:border-yellow-400 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                            <svg class="w-2 h-2 mr-2 {{ request()->routeIs('employee.index') ? 'fill-current text-[#FFF200] dark:text-yellow-400' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                                <circle cx="12" cy="12" r="10"/>
                                            </svg>
                                            <a href="{{ route('employee.index') }}" wire:navigate>
                                                {{ __('Employee List') }}
                                            </a>
                                        </div>
                                    </li>
                                    @endcan
                                    @if (request()->routeIs('employee.edit') || request()->routeIs('employee.payroll.*'))
                                    @can('edit_employee')
                                    <li>
                                        <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('employee.edit') || request()->routeIs('employee.payroll.*') ? 'border-[#FFF200] dark:border-yellow-400 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                            <svg class="w-2 h-2 mr-2 {{ request()->routeIs('employee.edit') || request()->routeIs('employee.payroll.*') ? 'fill-current text-[#FFF200] dark:text-yellow-400' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                                <circle cx="12" cy="12" r="10"/>
                                            </svg>
                                            <a href="">
                                                {{ __('Edit') }}
                                                @if (request()->routeIs('employee.edit'))
                                                    {{ __('Employee') }}
                                                @elseif (request()->routeIs('employee.payroll.allowances.*') || request()->routeIs('employee.payroll.allowances'))
                                                    {{ __('Allowances') }}
                                                @elseif (request()->routeIs('employee.payroll.deductions.*') || request()->routeIs('employee.payroll.deductions'))
                                                    {{ __('Deductions') }}
                                                @elseif (request()->routeIs('employee.payroll.payslips.*') || request()->routeIs('employee.payroll.payslips'))
                                                    {{ __('Payslips') }}
                                                @elseif (request()->routeIs('employee.payroll-history.*') || request()->routeIs('employee.payroll-history'))
                                                    {{ __('Payroll History') }}
                                                @endif
                                            </a>
                                        </div>
                                    </li>
                                    @endcan
                                    @else
                                    @can('create_employee')
                                    <li>
                                        <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('employee.show') ? 'border-[#FFF200] dark:border-yellow-400 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                            <svg class="w-2 h-2 mr-2 {{ request()->routeIs('employee.show') ? 'fill-current text-[#FFF200] dark:text-yellow-400' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                                <circle cx="12" cy="12" r="10"/>
                                            </svg>
                                            <a href="{{ route('employee.show') }}" wire:navigate>
                                                {{ __('Create Employee') }}
                                            </a>
                                        </div>
                                    </li>
                                    @endcan
                                    @endif
                                </ul>
                            </div>
                        </li>
                        @endif

                        @if (Auth::user()->can('manage_user') || Auth::user()->can('create_user'))
                        <li x-data="{ open: {{ (request()->routeIs('user.show') || request()->routeIs('user.index') || request()->routeIs('user.edit')) ? 'true' : 'false' }} }">
                            <div class="flex flex-col">
                                <button type="button"
                                    @click="open = !open"
                                    class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full font-semibold
                                    {{ request()->routeIs('user.show') || request()->routeIs('user.index') || request()->routeIs('user.edit')
                                        ? 'bg-[#FFF200] dark:bg-yellow-400 text-[#231F20] dark:text-zinc-900'
                                        : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }}">
                                    <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2
                                        {{ request()->routeIs('user.show') || request()->routeIs('user.index') || request()->routeIs('user.edit')
                                            ? 'bg-white dark:bg-zinc-900' : 'dark:bg-zinc-500' }}">
                                        <flux:icon name="user" variant="solid" class="w-4 h-4 text-zinc-500 dark:text-zinc-200" />
                                    </span>
                                    <span class="w-[60%] text-start">{{ __('User') }}</span>
                                    <svg :class="{ 'rotate-180': open }" class="w-4 h-4 text-zinc-400 transition-transform duration-300 ease-in-out" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M19 9l-7 7-7-7" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                                <ul x-show="open" 
                                    x-transition:enter="transition ease-out duration-300"
                                    x-transition:enter-start="opacity-0 transform -translate-y-2 scale-95"
                                    x-transition:enter-end="opacity-100 transform translate-y-0 scale-100"
                                    x-transition:leave="transition ease-in duration-200"
                                    x-transition:leave-start="opacity-100 transform translate-y-0 scale-100"
                                    x-transition:leave-end="opacity-0 transform -translate-y-2 scale-95"
                                    class="pl-8 mt-2 overflow-hidden" class="pl-8 mt-2">
                                    @can('manage_user')
                                    <li>
                                        <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('user.index') ? 'border-[#FFF200] dark:border-yellow-400 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                            <svg class="w-2 h-2 mr-2 {{ request()->routeIs('user.index') ? 'fill-current text-[#FFF200] dark:text-yellow-400' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                                <circle cx="12" cy="12" r="10"/>
                                            </svg>
                                            <a href="{{ route('user.index') }}" wire:navigate>
                                                {{ __('User List') }}
                                            </a>
                                        </div>
                                    </li>
                                    @endcan
                                    @can('create_user')
                                    <li>
                                        <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('user.show') ? 'border-[#FFF200] dark:border-yellow-400 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                            <svg class="w-2 h-2 mr-2 {{ request()->routeIs('user.show') ? 'fill-current text-[#FFF200] dark:text-yellow-400' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                                <circle cx="12" cy="12" r="10"/>
                                            </svg>
                                            <a href="{{ route('user.show') }}" wire:navigate>
                                                {{ __('Create User') }}
                                            </a>
                                        </div>
                                    </li>
                                    @endcan
                                </ul>
                            </div>
                        </li>
                        @endif

                        @if (Auth::user()->can('manage_job_advert') || Auth::user()->can('create_job_advert') || Auth::user()->can('audit_applications'))
                        <li x-data="{ open: {{ (request()->routeIs('job.index') || request()->routeIs('job.show') || request()->routeIs('job.edit') || request()->routeIs('job.index.vetting') || request()->routeIs('job.applications.audit')) ? 'true' : 'false' }} }">
                            <div class="flex flex-col">
                                <button type="button"
                                    @click="open = !open"
                                    class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full font-semibold
                                    {{ request()->routeIs('job.index') || request()->routeIs('job.show') || request()->routeIs('job.edit') || request()->routeIs('job.index.vetting') || request()->routeIs('job.applications.audit')
                                        ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200'
                                        : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }}">
                                    <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2
                                        {{ request()->routeIs('job.index') || request()->routeIs('job.show') || request()->routeIs('job.edit') || request()->routeIs('job.index.vetting') || request()->routeIs('job.applications.audit')
                                            ? 'bg-white dark:bg-zinc-900' : 'dark:bg-zinc-500' }}">
                                        <flux:icon name="briefcase" variant="solid" class="w-4 h-4 text-zinc-500 dark:text-zinc-200" />
                                    </span>
                                    <span class="w-[60%] text-start">{{ __('Adverts') }}</span>
                                    <svg :class="{ 'rotate-180': open }" class="w-4 h-4 text-zinc-400 transition-transform duration-300 ease-in-out" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M19 9l-7 7-7-7" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                                <ul x-show="open" 
                                    x-transition:enter="transition ease-out duration-300"
                                    x-transition:enter-start="opacity-0 transform -translate-y-2 scale-95"
                                    x-transition:enter-end="opacity-100 transform translate-y-0 scale-100"
                                    x-transition:leave="transition ease-in duration-200"
                                    x-transition:leave-start="opacity-100 transform translate-y-0 scale-100"
                                    x-transition:leave-end="opacity-0 transform -translate-y-2 scale-95"
                                    class="pl-8 mt-2 overflow-hidden" class="pl-8 mt-2">
                                    @can('manage_job_advert')
                                    <li>
                                        <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('job.index') ? 'border-green-600 dark:border-green-700 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                            <svg class="w-2 h-2 mr-2 {{ request()->routeIs('job.index') ? 'fill-current text-green-600 dark:text-green-700' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                                <circle cx="12" cy="12" r="10"/>
                                            </svg>
                                            <a href="{{ route('job.index') }}" wire:navigate>
                                                {{ __('Job Adverts') }}
                                            </a>
                                        </div>
                                    </li>
                                    @endcan
                                    @can('create_job_advert')
                                    <li>
                                        <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('job.show') ? 'border-green-600 dark:border-green-700 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                            <svg class="w-2 h-2 mr-2 {{ request()->routeIs('job.show') ? 'fill-current text-green-600 dark:text-green-700' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                                <circle cx="12" cy="12" r="10"/>
                                            </svg>
                                            <a href="{{ route('job.show') }}" wire:navigate>
                                                {{ __('Create Adverts') }}
                                            </a>
                                        </div>
                                    </li>
                                    @endcan
                                    {{-- @can('audit_applications')
                                    <li>
                                        <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('job.applications.audit') ? 'border-green-600 dark:border-green-700 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                            <svg class="w-2 h-2 mr-2 {{ request()->routeIs('job.applications.audit') ? 'fill-current text-green-600 dark:text-green-700' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                                <circle cx="12" cy="12" r="10"/>
                                            </svg>
                                            <a href="{{ route('job.applications.audit') }}" wire:navigate>
                                                {{ __('Audit Applications') }}
                                            </a>
                                        </div>
                                    </li>
                                    @endcan --}}
                                </ul>
                            </div>
                        </li>
                        @endif

                        @if (Auth::user()->can('manage_role') || Auth::user()->can('create_role'))
                        <li x-data="{ open: {{ (request()->routeIs('role.show') || request()->routeIs('role.index') || request()->routeIs('role.edit')) ? 'true' : 'false' }} }">
                            <div class="flex flex-col">
                                <button type="button"
                                    @click="open = !open"
                                    class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full font-semibold
                                    {{ request()->routeIs('role.show') || request()->routeIs('role.index') || request()->routeIs('role.edit')
                                        ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200'
                                        : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }}">
                                    <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2
                                        {{ request()->routeIs('role.show') || request()->routeIs('role.index') || request()->routeIs('role.edit')
                                            ? 'bg-white dark:bg-zinc-900' : 'dark:bg-zinc-500' }}">
                                        <flux:icon name="tag" variant="solid" class="w-4 h-4 text-zinc-500 dark:text-zinc-200" />
                                    </span>
                                    <span class="w-[60%] text-start">{{ __('Role') }}</span>
                                    <svg :class="{ 'rotate-180': open }" class="w-4 h-4 text-zinc-400 transition-transform duration-300 ease-in-out" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M19 9l-7 7-7-7" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                                <ul x-show="open" 
                                    x-transition:enter="transition ease-out duration-300"
                                    x-transition:enter-start="opacity-0 transform -translate-y-2 scale-95"
                                    x-transition:enter-end="opacity-100 transform translate-y-0 scale-100"
                                    x-transition:leave="transition ease-in duration-200"
                                    x-transition:leave-start="opacity-100 transform translate-y-0 scale-100"
                                    x-transition:leave-end="opacity-0 transform -translate-y-2 scale-95"
                                    class="pl-8 mt-2 overflow-hidden" class="pl-8 mt-2">
                                    @can('manage_role')
                                    <li>
                                        <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('role.index') ? 'border-green-600 dark:border-green-700 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                            <svg class="w-2 h-2 mr-2 {{ request()->routeIs('role.index') ? 'fill-current text-green-600 dark:text-green-700' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                                <circle cx="12" cy="12" r="10"/>
                                            </svg>
                                            <a href="{{ route('role.index') }}" wire:navigate>
                                                {{ __('Role List') }}
                                            </a>
                                        </div>
                                    </li>
                                    @endcan
                                    @can('create_role')
                                    <li>
                                        <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('role.show') ? 'border-green-600 dark:border-green-700 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                            <svg class="w-2 h-2 mr-2 {{ request()->routeIs('role.show') ? 'fill-current text-green-600 dark:text-green-700' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                                <circle cx="12" cy="12" r="10"/>
                                            </svg>
                                            <a href="{{ route('role.show') }}" wire:navigate>
                                                {{ __('Create Role') }}
                                            </a>
                                        </div>
                                    </li>
                                    @endcan
                                </ul>
                            </div>
                        </li>
                        @endif
                        
                        @if (Auth::user()->can('manage_location') || Auth::user()->can('create_location') || Auth::user()->can('manage_branch') || Auth::user()->can('create_branch') || Auth::user()->can('manage_department') || Auth::user()->can('create_department') || Auth::user()->can('manage_designation') || Auth::user()->can('create_designation'))
                        <li x-data="{ open: {{ (request()->routeIs('location.create') || request()->routeIs('location.manage') || request()->routeIs('location.edit') || request()->routeIs('branch.create') || request()->routeIs('branch.manage') || request()->routeIs('branch.edit') || request()->routeIs('department.create') || request()->routeIs('department.manage') || request()->routeIs('department.edit') || request()->routeIs('designation.create') || request()->routeIs('designation.manage') || request()->routeIs('designation.edit')) ? 'true' : 'false' }} }">
                            <div class="flex flex-col">
                                <button type="button"
                                    @click="open = !open"
                                    class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full font-semibold
                                    {{ request()->routeIs('location.create') || request()->routeIs('location.manage') || request()->routeIs('location.edit') || request()->routeIs('branch.create') || request()->routeIs('branch.manage') || request()->routeIs('branch.edit') || request()->routeIs('department.create') || request()->routeIs('department.manage') || request()->routeIs('department.edit') || request()->routeIs('designation.create') || request()->routeIs('designation.manage') || request()->routeIs('designation.edit')
                                        ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200'
                                        : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }}">
                                    <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2
                                        {{ request()->routeIs('location.create') || request()->routeIs('location.manage') || request()->routeIs('location.edit') || request()->routeIs('branch.create') || request()->routeIs('branch.manage') || request()->routeIs('branch.edit') || request()->routeIs('department.create') || request()->routeIs('department.manage') || request()->routeIs('department.edit') || request()->routeIs('designation.create') || request()->routeIs('designation.manage') || request()->routeIs('designation.edit')
                                            ? 'bg-white dark:bg-zinc-900' : 'dark:bg-zinc-500' }}">
                                        <flux:icon name="building-office" variant="solid" class="w-4 h-4 text-zinc-500 dark:text-zinc-200" />
                                    </span>
                                    <span class="w-[60%] text-start">{{ __("Organization") }}</span>
                                    <svg :class="{ 'rotate-180': open }" class="w-4 h-4 text-zinc-400 transition-transform duration-300 ease-in-out" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M19 9l-7 7-7-7" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                                <ul x-show="open" 
                                    x-transition:enter="transition ease-out duration-300"
                                    x-transition:enter-start="opacity-0 transform -translate-y-2 scale-95"
                                    x-transition:enter-end="opacity-100 transform translate-y-0 scale-100"
                                    x-transition:leave="transition ease-in duration-200"
                                    x-transition:leave-start="opacity-100 transform translate-y-0 scale-100"
                                    x-transition:leave-end="opacity-0 transform -translate-y-2 scale-95"
                                    class="pl-8 mt-2 overflow-hidden" class="pl-8 mt-2">
                                    @can('manage_location')
                                    <li>
                                        <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('location.manage') ? 'border-green-600 dark:border-green-700 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                            <svg class="w-2 h-2 mr-2 {{ request()->routeIs('location.manage') ? 'fill-current text-green-600 dark:text-green-700' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                            </svg>
                                            <a href="{{ route('location.manage') }}" wire:navigate>
                                                {{ __('Locations') }}
                                            </a>
                                        </div>
                                    </li>
                                    @endcan

                                    @can('manage_branch')
                                    <li>
                                        <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('branch.manage') ? 'border-green-600 dark:border-green-700 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                            <svg class="w-2 h-2 mr-2 {{ request()->routeIs('branch.manage') ? 'fill-current text-green-600 dark:text-green-700' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                            </svg>
                                            <a href="{{ route('branch.manage') }}" wire:navigate>
                                                {{ __('Branches') }}
                                            </a>
                                        </div>
                                    </li>
                                    @endcan

                                    @can('manage_department')
                                    <li>
                                        <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('department.manage') ? 'border-green-600 dark:border-green-700 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                            <svg class="w-2 h-2 mr-2 {{ request()->routeIs('department.manage') ? 'fill-current text-green-600 dark:text-green-700' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                            </svg>
                                            <a href="{{ route('department.manage') }}" wire:navigate>
                                                {{ __('Departments') }}
                                            </a>
                                        </div>
                                    </li>
                                    @endcan

                                    @can('manage_designation')
                                    <li>
                                        <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('designation.manage') ? 'border-green-600 dark:border-green-700 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                            <svg class="w-2 h-2 mr-2 {{ request()->routeIs('designation.manage') ? 'fill-current text-green-600 dark:text-green-700' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                            </svg>
                                            <a href="{{ route('designation.manage') }}" wire:navigate>
                                                {{ __('Designations') }}
                                            </a>
                                        </div>
                                    </li>
                                    @endcan
                                </ul>
                            </div>
                        </li>
                        @endif

                        @if (Auth::user()->can('manage_my_leave') || Auth::user()->can('apply_for_leave') || Auth::user()->can('manage_all_leaves') || Auth::user()->can('manage_leave_type') || Auth::user()->can('create_leave_type'))
                        <li x-data="{ open: {{ (request()->routeIs('own-leave.manage') || request()->routeIs('leave.apply') || request()->routeIs('own-leave.edit') || request()->routeIs('all-leave.manage') || request()->routeIs('all-leave.edit')) ? 'true' : 'false' }} }">
                            <div class="flex flex-col">
                                <button type="button"
                                    @click="open = !open"
                                    class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full font-semibold
                                    {{ request()->routeIs('own-leave.manage') || request()->routeIs('leave.apply') || request()->routeIs('own-leave.edit') || request()->routeIs('all-leave.manage') || request()->routeIs('all-leave.edit')
                                        ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200'
                                        : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }}">
                                    <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2
                                        {{ request()->routeIs('own-leave.manage') || request()->routeIs('leave.apply') || request()->routeIs('own-leave.edit') || request()->routeIs('all-leave.manage') || request()->routeIs('all-leave.edit')
                                            ? 'bg-white dark:bg-zinc-900' : 'dark:bg-zinc-500' }}">
                                        <flux:icon name="building-office" variant="solid" class="w-4 h-4 text-zinc-500 dark:text-zinc-200" />
                                    </span>
                                    <span class="w-[60%] text-start">{{ __("Leave") }}</span>
                                    <svg :class="{ 'rotate-180': open }" class="w-4 h-4 text-zinc-400 transition-transform duration-300 ease-in-out" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M19 9l-7 7-7-7" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                                <ul x-show="open" 
                                    x-transition:enter="transition ease-out duration-300"
                                    x-transition:enter-start="opacity-0 transform -translate-y-2 scale-95"
                                    x-transition:enter-end="opacity-100 transform translate-y-0 scale-100"
                                    x-transition:leave="transition ease-in duration-200"
                                    x-transition:leave-start="opacity-100 transform translate-y-0 scale-100"
                                    x-transition:leave-end="opacity-0 transform -translate-y-2 scale-95"
                                    class="pl-8 mt-2 overflow-hidden" class="pl-8 mt-2">
                                    <li class="mb-2">
                                        <div class="flex items-center gap-2 px-2 py-1">
                                            <flux:icon name="document" variant="solid" class="w-4 h-4 text-green-600 dark:text-green-400" />
                                            <span class="font-semibold text-zinc-700 dark:text-zinc-200">{{ __('My Requests') }}</span>
                                        </div>
                                        <flux:menu.separator class="my-2 border-green-600 dark:border-green-400" />
                                    </li>
                                    @can('apply_for_leave')
                                    <li>
                                        <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('leave.apply') ? 'border-green-600 dark:border-green-700 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                            <svg class="w-2 h-2 mr-2 {{ request()->routeIs('leave.apply') ? 'fill-current text-green-600 dark:text-green-700' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                            </svg>
                                            <a href="{{ route('leave.apply') }}" wire:navigate>
                                                {{ __('Apply Leave') }}
                                            </a>
                                        </div>
                                    </li>
                                    @endcan
                                    @can('manage_my_leave')
                                    <li>
                                        <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('own-leave.manage') ? 'border-green-600 dark:border-green-700 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                            <svg class="w-2 h-2 mr-2 {{ request()->routeIs('own-leave.manage') ? 'fill-current text-green-600 dark:text-green-700' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                            </svg>
                                            <a href="{{ route('own-leave.manage') }}" wire:navigate>
                                                {{ __('Leave List') }}
                                            </a>
                                        </div>
                                    </li>
                                    @endcan

                                    @can('manage_all_leaves')
                                    <li class="mb-2">
                                        <div class="flex items-center gap-2 px-2 py-1">
                                            <flux:icon name="document" variant="solid" class="w-4 h-4 text-green-600 dark:text-green-400" />
                                            <span class="font-semibold text-zinc-700 dark:text-zinc-200">{{ __('Other Requests') }}</span>
                                        </div>
                                        <flux:menu.separator class="my-2 border-green-600 dark:border-green-400" />
                                    </li>
                                    <li>
                                        <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('all-leave.manage') ? 'border-green-600 dark:border-green-700 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                            <svg class="w-2 h-2 mr-2 {{ request()->routeIs('all-leave.manage') ? 'fill-current text-green-600 dark:text-green-700' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                            </svg>
                                            <a href="{{ route('all-leave.manage') }}" wire:navigate>
                                                {{ __('All Leave List') }}
                                            </a>
                                        </div>
                                    </li>
                                    @endcan
                                </ul>
                            </div>
                        </li>
                        @endif
                        
                        @if (Auth::user()->can('process_payroll') || Auth::user()->can('view_my_payslips'))
                        <li x-data="{ open: {{ (request()->routeIs('payroll.process') || request()->routeIs('payroll.employee')) ? 'true' : 'false' }} }">
                            <div class="flex flex-col">
                                <button type="button"
                                    @click="open = !open"
                                    class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full font-semibold
                                    {{ request()->routeIs('payroll.process') || request()->routeIs('payroll.employee')
                                        ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200'
                                        : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }}">
                                    <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2
                                        {{ request()->routeIs('payroll.process') || request()->routeIs('payroll.employee')
                                            ? 'bg-white dark:bg-zinc-900' : 'dark:bg-zinc-500' }}">
                                        <flux:icon name="building-office" variant="solid" class="w-4 h-4 text-zinc-500 dark:text-zinc-200" />
                                    </span>
                                    <span class="w-[60%] text-start">{{ __("Payroll") }}</span>
                                    <svg :class="{ 'rotate-180': open }" class="w-4 h-4 text-zinc-400 transition-transform duration-300 ease-in-out" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M19 9l-7 7-7-7" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                                <ul x-show="open" 
                                    x-transition:enter="transition ease-out duration-300"
                                    x-transition:enter-start="opacity-0 transform -translate-y-2 scale-95"
                                    x-transition:enter-end="opacity-100 transform translate-y-0 scale-100"
                                    x-transition:leave="transition ease-in duration-200"
                                    x-transition:leave-start="opacity-100 transform translate-y-0 scale-100"
                                    x-transition:leave-end="opacity-0 transform -translate-y-2 scale-95"
                                    class="pl-8 mt-2 overflow-hidden" class="pl-8 mt-2">
                                    @can('process_payroll')
                                    <li>
                                        <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('payroll.process') ? 'border-green-600 dark:border-green-700 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                            <svg class="w-2 h-2 mr-2 {{ request()->routeIs('payroll.process') ? 'fill-current text-green-600 dark:text-green-700' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                            </svg>
                                            <a href="{{ route('payroll.process') }}" wire:navigate>
                                                {{ __('Process Payroll') }}
                                            </a>
                                        </div>
                                    </li>
                                    @endcan

                                    @can('view_my_payslips')
                                    <li>
                                        <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('payroll.employee') ? 'border-green-600 dark:border-green-700 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                            <svg class="w-2 h-2 mr-2 {{ request()->routeIs('payroll.employee') ? 'fill-current text-green-600 dark:text-green-700' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                            </svg>
                                            <a href="{{ route('payroll.employee') }}" wire:navigate>
                                                {{ __('My Payslips') }}
                                            </a>
                                        </div>
                                    </li>
                                    @endcan
                                </ul>
                            </div>
                        </li>
                        @endif
                    </ul>
                </nav>
                
                <!-- Desktop User Menu -->
                <flux:dropdown class="hidden lg:block" position="bottom" align="start">
                    <flux:profile
                        :name="auth()->user()->name"
                        :initials="auth()->user()->initials()"
                        icon:trailing="chevrons-up-down"
                    />
                    <flux:menu class="w-[220px]">
                        <flux:menu.radio.group>
                            <div class="p-0 text-sm font-normal">
                                <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                    <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                        <span class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                            {{ auth()->user()->initials() }}
                                        </span>
                                    </span>
                                    <div class="grid flex-1 text-start text-sm leading-tight">
                                        <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                        <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                    </div>
                                </div>
                            </div>
                        </flux:menu.radio.group>
                        <flux:menu.separator />
                        <flux:menu.radio.group>
                            <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                        </flux:menu.radio.group>
                        <flux:menu.separator />
                        <flux:menu.separator />
                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                                {{ __('Log Out') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            </div>
        </flux:sidebar>

        <!-- Mobile Header -->
        <flux:header
            class="lg:hidden sticky top-0 border-b transition-colors duration-300"
            x-data="{ 'scrolled': false }"
            @scroll.window="scrolled = (window.pageYOffset > 10)"
            x-bind:class="scrolled
                ? 'py-4 bg-white/60 dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700 shadow-md backdrop-blur-sm transition-shadow duration-300 ease-in-out'
                : 'py-4 bg-white dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700 transition-shadow duration-300 ease-in-out'"
        >
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 ml-4" wire:navigate>
                <x-app-logo class="h-8 w-8" />
            </a>
            <flux:spacer />
            <!-- Mobile User Menu -->
            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />
                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>
                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>
                    <flux:menu.separator />
                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>
                    <flux:menu.separator />
                    <flux:menu.separator />
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        {{-- add 5 svg bloobs --}}
        <svg class="fixed -z-[1] top-0 left-0 w-64 h-64 -translate-x-1/2 -translate-y-1/2 opacity-20 blur-3xl" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <radialGradient id="gradient1" cx="50%" cy="50%" r="50%">
                    <stop offset="0%" stop-color="#34D399" />
                    <stop offset="100%" stop-color="#10B981" stop-opacity="0" />
                </radialGradient>
            </defs>
            <circle cx="100" cy="100" r="100" fill="url(#gradient1)" />
        </svg>
        <svg class="fixed -z-[1] top-1/4 right-0 w-64 h-64 translate-x-1/2 -translate-y-1/2 opacity-20 blur-3xl" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <radialGradient id="gradient2" cx="50%" cy="50%" r="50%">
                    <stop offset="0%" stop-color="#3B82F6" />
                    <stop offset="100%" stop-color="#2563EB" stop-opacity="0" />
                </radialGradient>
            </defs>
            <circle cx="100" cy="100" r="100" fill="url(#gradient2)" />
        </svg>
        <svg class="fixed -z-[1] bottom-0 left-1/4 w-64 h-64 -translate-x-1/2 translate-y-1/2 opacity-20 blur-3xl" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <radialGradient id="gradient3" cx="50%" cy="50%" r="50%">
                    <stop offset="0%" stop-color="#FBBF24" />
                    <stop offset="100%" stop-color="#F59E0B" stop-opacity="0" />
                </radialGradient>
            </defs>
            <circle cx="100" cy="100" r="100" fill="url(#gradient3)" />
        </svg>
        <svg class="fixed -z-[1] bottom-1/4 right-1/4 w-64 h-64 translate-x-1/2 translate-y-1/2 opacity-20 blur-3xl" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <radialGradient id="gradient4" cx="50%" cy="50%" r="50%">
                    <stop offset="0%" stop-color="#A78BFA" />
                    <stop offset="100%" stop-color="#8B5CF6" stop-opacity="0" />
                </radialGradient>
            </defs>
            <circle cx="100" cy="100" r="100" fill="url(#gradient4)" />
        </svg>
        <svg class="fixed -z-[1] top-1/2 left-1/2 w-64 h-64 -translate-x-1/2 -translate-y-1/2 opacity-20 blur-3xl" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <radialGradient id="gradient5" cx="50%" cy="50%" r="50%">
                    <stop offset="0%" stop-color="#F87171" />
                    <stop offset="100%" stop-color="#EF4444" stop-opacity="0" />
                </radialGradient>
            </defs>
            <circle cx="100" cy="100" r="100" fill="url(#gradient5)" />
        </svg>
        
        @livewire('notifications.popup-manager')
        @fluxScripts
        
    </body>
</html>