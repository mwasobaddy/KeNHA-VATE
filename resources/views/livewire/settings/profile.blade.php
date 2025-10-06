<?php

use App\Models\Department;
use App\Models\Region;
use App\Models\User;
use App\Services\StaffService;
use App\Services\UserService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component {
    public string $username = '';
    public string $email = '';
    public string $first_name = '';
    public string $other_names = '';
    public string $gender = '';
    public string $mobile_phone = '';
    public string $password = '';
    public string $password_confirmation = '';
    public ?string $staff_number = null;
    public ?string $personal_email = null;
    public ?string $job_title = null;
    public ?int $department_id = null;
    public ?string $employment_type = null;
    public ?string $supervisor_email = null;
    public bool $is_initial_setup = false;
    public bool $is_kenha_staff = false;

    public $regions = [];
    public $departments = [];

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();

        // Check if this is initial profile setup
        $this->is_initial_setup = !$user->staff || !$user->staff->isProfileComplete();

        $this->username = $user->username;
        $this->email = $user->email;
        $this->first_name = $user->first_name ?: '';
        $this->other_names = $user->other_names ?: '';
        $this->gender = $user->gender ?: '';
        $this->mobile_phone = $user->mobile_phone ?: '';

        // Load staff data if exists
        if ($user->staff) {
            $this->staff_number = $user->staff->staff_number;
            $this->personal_email = $user->staff->personal_email;
            $this->job_title = $user->staff->job_title;
            $this->department_id = $user->staff->department_id;
            $this->employment_type = $user->staff->employment_type;
            $this->supervisor_email = $user->staff->supervisor?->email;
            $this->is_kenha_staff = !empty($user->staff->staff_number);
        }

        // Determine if user is KeNHA staff based on email
        $isKenhaEmail = str_ends_with($user->email, '@kenha.co.ke');
        if ($isKenhaEmail && !$this->is_kenha_staff) {
            $this->is_kenha_staff = true;
        }
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();
        $rules = $this->getValidationRules($user);

        $validated = $this->validate($rules);

        // Update user basic info
        $user->fill([
            'username' => $validated['username'] ?? $user->username,
            'first_name' => $validated['first_name'],
            'other_names' => $validated['other_names'],
            'gender' => $validated['gender'],
            'mobile_phone' => $validated['mobile_phone'],
            'email' => $validated['email'],
        ]);

        // Set password during initial setup
        if ($this->is_initial_setup && isset($validated['password'])) {
            $user->password = bcrypt($validated['password']);
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        // Update or create staff profile
        $userService = app(UserService::class);
        $staffData = [
            'staff_number' => $validated['staff_number'] ?? null,
            'personal_email' => $validated['personal_email'] ?? null,
            'job_title' => $validated['job_title'] ?? null,
            'department_id' => $validated['department_id'],
            'employment_type' => $validated['employment_type'] ?? null,
        ];

        if ($this->is_kenha_staff) {
            $staffData = array_merge($staffData, [
                'staff_number' => $validated['staff_number'],
                'personal_email' => $validated['personal_email'],
                'job_title' => $validated['job_title'],
                'employment_type' => $validated['employment_type'],
            ]);
        } else {
            $staffData['employment_type'] = $validated['employment_type'];
            $staffData['supervisor_email'] = $validated['supervisor_email'];
        }

        if ($user->staff) {
            // Update existing staff profile
            $userService->updateStaffProfile($user->staff, $staffData);
        } else {
            // Create new staff profile
            $userService->createStaffProfile($user, $staffData);

            // If not KeNHA staff, request supervisor approval
            if (!$this->is_kenha_staff && $this->supervisor_email) {
                $staffService = app(StaffService::class);
                $staffService->requestSupervisorApproval($user->staff);
            }
        }

        // Fire profile completed event for initial setup
        if ($this->is_initial_setup) {
            \App\Events\ProfileCompleted::dispatch($user);
            $this->is_initial_setup = false; // Mark as completed
            session()->flash('success', 'Profile completed successfully! Please review the terms and conditions.');
            $this->redirect(route('terms.show'), navigate: true);
            return;
        }

        $this->dispatch('profile-updated', username: $user->username);
    }

    /**
     * Get validation rules based on user type and setup mode.
     */
    protected function getValidationRules($user): array
    {
        $rules = [
            'username' => [
                'required',
                'string',
                'min:4',
                'max:15',
                'regex:/^[a-zA-Z0-9](?:[a-zA-Z0-9._]*[a-zA-Z0-9])?$/',
                Rule::unique(User::class)->ignore($user->id)
            ],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                Rule::unique(User::class)->ignore($user->id),
                'max:255'
                // Note: Email cannot be changed by user - contact admin if needed
            ],
            'first_name' => $this->is_initial_setup ? 'required|string|max:100' : 'nullable|string|max:100',
            'other_names' => $this->is_initial_setup ? 'required|string|max:100' : 'nullable|string|max:100',
            'gender' => $this->is_initial_setup ? ['required', Rule::in(array_keys(config('kenhavate.gender_options')))] : ['nullable', Rule::in(array_keys(config('kenhavate.gender_options')))],
            'mobile_phone' => $this->is_initial_setup ? 'required|string|regex:/^\+254\d{9}$/|unique:users,mobile_phone' : 'nullable|string|regex:/^\+254\d{9}$/|unique:users,mobile_phone',
            'department_id' => $this->is_initial_setup ? 'required|exists:departments,id' : 'nullable|exists:departments,id',
        ];

        if ($this->is_initial_setup) {
            // Add password validation for initial setup
            $rules['password'] = 'required|string|min:8|confirmed';
        }

        if ($this->is_kenha_staff) {
            $staffRequired = $this->is_initial_setup ? 'required' : 'nullable';
            $rules = array_merge($rules, [
                'staff_number' => $this->is_initial_setup ? 'required|string|unique:staff,staff_number,' . ($user->staff?->id ?? 'NULL') : 'nullable|string|unique:staff,staff_number,' . ($user->staff?->id ?? 'NULL'),
                'personal_email' => 'nullable|email|different:email',
                'job_title' => $staffRequired . '|string|max:150',
                'employment_type' => [$staffRequired, Rule::in(array_keys(config('kenhavate.employment_types')))],
            ]);
        } elseif ($this->is_initial_setup) {
            $rules = array_merge($rules, [
                'employment_type' => ['required', Rule::in(array_keys(config('kenhavate.employment_types')))],
                'supervisor_email' => [
                    'required',
                    'email',
                    'different:email',
                    'regex:/@kenha\.co\.ke$/',
                ],
            ]);
        }

        return $rules;
    }

    /**
     * Validate a specific field only.
     */
    public function validateField(string $field): void
    {
        $user = Auth::user();
        $rules = $this->getValidationRules($user);

        if (isset($rules[$field])) {
            $this->validate([$field => $rules[$field]]);
        }
    }

    /**
     * Get departments for selected region/directorate.
     */
    public function getDepartmentsProperty(): array
    {
        if (!$this->department_id) {
            return [];
        }

        return Department::active()
            ->where('id', $this->department_id)
            ->with('directorate.region')
            ->get()
            ->toArray();
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="$is_initial_setup ? __('Complete Your Profile') : __('Profile')" :subheading="$is_initial_setup ? __('Please complete your profile information to continue') : __('Update your account information')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <!-- Basic Account Information -->
            <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 mb-4">
                    {{ __('Account Information') }}
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input wire:model.defer="username" wire:blur="validateField('username')" :label="__('Username')" type="text" required autofocus autocomplete="username" />

                    <flux:input wire:model.defer="email" :label="__('Email')" type="email" required autocomplete="email" />
                </div>

                @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail &&! auth()->user()->hasVerifiedEmail())
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </flux:link>
                        </flux:text>

                        @if (session('status') === 'verification-link-sent')
                            <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </flux:text>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Personal Information -->
            <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 mb-4">
                    {{ __('Personal Information') }}
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input
                        wire:model.defer="first_name"
                        :label="__('First Name')"
                        :required="$is_initial_setup"
                    />

                    <flux:input
                        wire:model.defer="other_names"
                        :label="__('Other Names')"
                        :required="$is_initial_setup"
                    />

                    <flux:select
                        wire:model.defer="gender"
                        :label="__('Gender')"
                        :required="$is_initial_setup"
                        placeholder="Select gender"
                    >
                        @foreach(config('kenhavate.gender_options') as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>

                    <flux:input
                        wire:model.defer="mobile_phone"
                        :label="__('Mobile Phone')"
                        type="tel"
                        :required="$is_initial_setup"
                        placeholder="+254XXXXXXXXX"
                    />
                </div>
            </div>

            @if($is_initial_setup)
            <!-- Password Setup -->
            <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 mb-4">
                    {{ __('Set Password') }}
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input
                        wire:model.defer="password"
                        :label="__('Password')"
                        type="password"
                        required
                        autocomplete="new-password"
                        :placeholder="__('Password')"
                        viewable
                    />

                    <flux:input
                        wire:model.defer="password_confirmation"
                        :label="__('Confirm Password')"
                        type="password"
                        required
                        autocomplete="new-password"
                        :placeholder="__('Confirm Password')"
                        viewable
                    />
                </div>
            </div>
            @endif

            @if($is_kenha_staff)
                <!-- KeNHA Staff Information -->
                <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                    <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 mb-4">
                        {{ __('KeNHA Staff Information') }}
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:input
                            wire:model.defer="staff_number"
                            :label="__('Staff Number')"
                            required
                        />

                        <flux:input
                            wire:model.defer="personal_email"
                            :label="__('Personal Email (Optional)')"
                            type="email"
                        />

                        <flux:input
                            wire:model.defer="job_title"
                            :label="__('Job Title/Designation')"
                            required
                        />

                        <flux:select
                            wire:model.defer="employment_type"
                            :label="__('Employment Type')"
                            required
                            placeholder="Select employment type"
                        >
                            @foreach(config('kenhavate.employment_types') as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>
                

                <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                    <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 mb-4">
                        {{ __('Employment Information') }}
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:select
                            wire:model.defer="employment_type"
                            :label="__('Employment Type')"
                            required
                            placeholder="Select employment type"
                        >
                            @foreach(config('kenhavate.employment_types') as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </flux:select>

                        <flux:input
                            wire:model.defer="supervisor_email"
                            :label="__('Supervisor\'s Email')"
                            type="email"
                            required
                            placeholder="supervisor@kenha.co.ke"
                        />
                    </div>

                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                        {{ __('Your supervisor will need to approve your staff status.') }}
                    </p>
                </div>

                <!-- Department Selection -->
                <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                    <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 mb-4">
                        {{ __('Department Assignment') }}
                    </h3>

                    <flux:select
                        wire:model.defer="department_id"
                        :label="__('Department')"
                        required
                        placeholder="Select your department"
                    >
                        @foreach($regions as $region)
                            <optgroup :label="__('{{ $region->name }}')">
                                @foreach($region->directorates as $directorate)
                                    @foreach($directorate->departments as $department)
                                        <option value="{{ $department->id }}">
                                            {{ $region->name }} → {{ $directorate->name }} → {{ $department->name }}
                                        </option>
                                    @endforeach
                                @endforeach
                            </optgroup>
                        @endforeach
                    </flux:select>
                </div>
            @endif

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full" data-test="update-profile-button">
                        {{ $is_initial_setup ? __('Complete Profile') : __('Save') }}
                    </flux:button>
                </div>

                <x-action-message class="me-3" on="profile-updated">
                    {{ $is_initial_setup ? __('Profile completed successfully!') : __('Saved.') }}
                </x-action-message>
            </div>
        </form>

        <livewire:settings.delete-user-form />
    </x-settings.layout>
</section>
