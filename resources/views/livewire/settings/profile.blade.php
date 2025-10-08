<?php

use App\Models\Department;
use App\Models\Region;
use App\Models\User;
use App\Services\AuditService;
use App\Services\StaffService;
use App\Services\UserService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
    public bool $is_other_type_staff = false;
    public bool $wants_to_be_staff = false;

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

        $this->username = $user->username ?: '';
        $this->email = $user->email ?: '';
        $this->first_name = $user->first_name ?: '';
        $this->other_names = $user->other_names ?: '';
        $this->gender = $user->gender ?: '';
        $this->mobile_phone = $user->mobile_phone ?: '';

        // Determine if user has KeNHA email
        $hasKenhaEmail = str_ends_with($user->email, '@kenha.co.ke');

        // Load staff data if exists
        if ($user->staff) {
            $this->staff_number = $user->staff->staff_number;
            $this->personal_email = $user->staff->personal_email;
            $this->job_title = $user->staff->job_title;
            $this->department_id = $user->staff->department_id;
            $this->employment_type = $user->staff->employment_type;
            $this->supervisor_email = $user->staff->supervisor?->email;
            
            // If they have a staff record, they've chosen to be staff
            $this->wants_to_be_staff = true;
            
            // If they have staff_number or kenha email, they're kenha staff
            $this->is_kenha_staff = !empty($user->staff->staff_number) || $hasKenhaEmail;
            
            // If they want to be staff but don't have kenha email/staff_number
            $this->is_other_type_staff = !$this->is_kenha_staff;
        } else {
            // New user - set kenha staff if they have kenha email
            $this->is_kenha_staff = $hasKenhaEmail;
        }

        // Load departments for KeNHA staff
        if ($this->is_kenha_staff || $hasKenhaEmail) {
            $this->departments = Department::active()
                ->with(['directorate.region'])
                ->orderBy('name')
                ->get()
                ->toArray();
        }

        // Load regions for non-KeNHA staff who want to be staff
        if ($this->is_other_type_staff || ($this->wants_to_be_staff && !$this->is_kenha_staff)) {
            $this->regions = Region::active()
                ->with(['directorates.departments' => function ($query) {
                    $query->active()->orderBy('name');
                }])
                ->orderBy('name')
                ->get();
        }
    }

    /**
     * Handle checkbox toggle for non-kenha staff
     */
    public function updatedWantsToBeStaff($value): void
    {
        $this->wants_to_be_staff = $value;
        $hasKenhaEmail = str_ends_with($this->email, '@kenha.co.ke');
        
        if ($value && !$hasKenhaEmail) {
            // User wants to be staff but doesn't have kenha email
            $this->is_other_type_staff = true;
            $this->is_kenha_staff = false;
            
            // Load regions if not already loaded
            if (empty($this->regions)) {
                $this->regions = Region::active()
                    ->with(['directorates.departments' => function ($query) {
                        $query->active()->orderBy('name');
                    }])
                    ->orderBy('name')
                    ->get();
            }
        } else {
            // User doesn't want to be staff
            $this->is_other_type_staff = false;
            $this->staff_number = null;
            $this->personal_email = null;
            $this->job_title = null;
            $this->department_id = null;
            $this->employment_type = null;
            $this->supervisor_email = null;
        }
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();
        $hasKenhaEmail = str_ends_with($user->email, '@kenha.co.ke');
        
        Log::info('Starting profile update for user: ' . $user->id, [
            'email' => $user->email,
            'is_initial_setup' => $this->is_initial_setup,
            'is_kenha_staff' => $this->is_kenha_staff,
            'is_other_type_staff' => $this->is_other_type_staff,
            'wants_to_be_staff' => $this->wants_to_be_staff,
            'has_kenha_email' => $hasKenhaEmail,
        ]);

        try {
            $rules = $this->getValidationRules($user);
            $validated = $this->validate($rules);

            $auditService = app(AuditService::class);

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
            Log::info('User basic info saved successfully for user: ' . $user->id);

            // Audit log: Profile updated
            $auditService->logUserActivity($user, 'profile_updated', [
                'is_initial_setup' => $this->is_initial_setup,
                'fields_updated' => array_keys($user->getChanges()),
            ]);

            // Handle staff profile only if user wants to be staff or has kenha email
            if ($this->wants_to_be_staff || $this->is_kenha_staff || $hasKenhaEmail) {
                $userService = app(UserService::class);
                $staffData = [
                    'personal_email' => $validated['personal_email'] ?? null,
                    'job_title' => $validated['job_title'] ?? null,
                    'department_id' => $validated['department_id'] ?? null,
                    'employment_type' => $validated['employment_type'] ?? null,
                ];

                // Only add staff_number for KeNHA staff
                if ($this->is_kenha_staff || $hasKenhaEmail) {
                    $staffData['staff_number'] = $validated['staff_number'] ?? null;
                }

                // Handle supervisor for non-KeNHA staff
                if ($this->is_other_type_staff && !empty($validated['supervisor_email'])) {
                    $supervisor = User::where('email', $validated['supervisor_email'])->first();
                    if ($supervisor && $supervisor->staff) {
                        $staffData['supervisor_id'] = $supervisor->id;
                        Log::info('Supervisor found and set for user: ' . $user->id, ['supervisor_id' => $supervisor->id]);
                    } else {
                        Log::warning('Supervisor not found or no staff profile for email: ' . $validated['supervisor_email']);
                        throw new \Exception('Supervisor not found. Please ensure the email belongs to a registered KeNHA staff member.');
                    }
                }

                if ($user->staff) {
                    // Update existing staff profile
                    Log::info('Updating existing staff profile for user: ' . $user->id);
                    $userService->updateStaffProfile($user->staff, $staffData);
                    Log::info('Staff profile updated successfully for user: ' . $user->id);

                    // Audit log: Staff profile updated
                    $auditService->logUserActivity($user, 'staff_profile_updated', [
                        'staff_id' => $user->staff->id,
                        'fields_updated' => array_keys($staffData),
                    ]);
                } else {
                    // Create new staff profile
                    Log::info('Creating new staff profile for user: ' . $user->id);
                    $userService->createStaffProfile($user, $staffData);
                    Log::info('Staff profile created successfully for user: ' . $user->id);

                    // Audit log: Staff profile created
                    $auditService->logUserActivity($user, 'staff_profile_created', [
                        'staff_data' => $staffData,
                    ]);

                    // If not KeNHA staff, request supervisor approval
                    if ($this->is_other_type_staff && $this->supervisor_email) {
                        $staffService = app(StaffService::class);
                        $staffService->requestSupervisorApproval($user->staff);
                        Log::info('Supervisor approval requested for user: ' . $user->id);

                        // Audit log: Supervisor approval requested
                        $auditService->logUserActivity($user, 'supervisor_approval_requested', [
                            'supervisor_email' => $this->supervisor_email,
                            'staff_id' => $user->staff->id,
                        ]);
                    }
                }
            }

            // Fire profile completed event for initial setup
            if ($this->is_initial_setup) {
                \App\Events\ProfileCompleted::dispatch($user);
                $this->is_initial_setup = false;
                session()->flash('success', 'Profile completed successfully! Please review the terms and conditions.');
                Log::info('Profile completed event dispatched for user: ' . $user->id);

                // Audit log: Profile completed
                $auditService->logUserActivity($user, 'profile_completed', [
                    'is_kenha_staff' => $this->is_kenha_staff,
                    'is_other_type_staff' => $this->is_other_type_staff,
                    'has_staff_profile' => $user->staff ? true : false,
                ]);

                $this->redirect(route('terms.show'), navigate: true);
                return;
            }

            // Set success notification for profile update
            $this->dispatch('showSuccess', 'Profile Updated', 'Your profile has been updated successfully.');

            Log::info('Profile update completed successfully for user: ' . $user->id);

        } catch (\Exception $e) {
            Log::error('Error updating profile for user: ' . $user->id, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Use popup notification for business logic errors
            $this->dispatch('showError', 'Profile Update Failed', $e->getMessage());
        }
    }

    /**
     * Get validation rules based on user type and setup mode.
     */
    protected function getValidationRules($user): array
    {
        $hasKenhaEmail = str_ends_with($user->email, '@kenha.co.ke');
        
        // Base rules for all users
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
            ],
            'first_name' => $this->is_initial_setup ? 'required|string|max:100' : 'nullable|string|max:100',
            'other_names' => $this->is_initial_setup ? 'required|string|max:100' : 'nullable|string|max:100',
            'gender' => $this->is_initial_setup 
                ? ['required', Rule::in(array_keys(config('kenhavate.gender_options')))] 
                : ['nullable', Rule::in(array_keys(config('kenhavate.gender_options')))],
            'mobile_phone' => $this->is_initial_setup
                ? ['required', 'string', 'regex:/^\(\+254\)\s\d{3}-\d{6}$/', Rule::unique('users', 'mobile_phone')->ignore($user->id)]
                : ['nullable', 'string', 'regex:/^\(\+254\)\s\d{3}-\d{6}$/', Rule::unique('users', 'mobile_phone')->ignore($user->id)],
        ];

        // Password required for initial setup
        if ($this->is_initial_setup) {
            $rules['password'] = 'required|string|min:8|confirmed';
        }

        // Staff-specific rules
        if ($this->wants_to_be_staff || $hasKenhaEmail) {
            $staffRequired = $this->is_initial_setup ? 'required' : 'nullable';
            
            // Rules for KeNHA staff (with @kenha.co.ke email)
            if ($this->is_kenha_staff || $hasKenhaEmail) {
                $rules = array_merge($rules, [
                    'staff_number' => [
                        $staffRequired,
                        'string',
                        Rule::unique('staff', 'staff_number')->ignore($user->staff?->id)
                    ],
                    'personal_email' => 'nullable|email|different:email',
                    'job_title' => $staffRequired . '|string|max:150',
                    'department_id' => $staffRequired . '|exists:departments,id',
                    'employment_type' => [$staffRequired, Rule::in(array_keys(config('kenhavate.employment_types')))],
                ]);
            }
            // Rules for other staff (without @kenha.co.ke email)
            elseif ($this->is_other_type_staff) {
                $rules = array_merge($rules, [
                    'employment_type' => ['required', Rule::in(array_keys(config('kenhavate.employment_types')))],
                    'department_id' => 'required|exists:departments,id',
                    'supervisor_email' => [
                        'required',
                        'email',
                        'different:email',
                        'regex:/@kenha\.co\.ke$/',
                        'exists:users,email',
                    ],
                ]);
            }
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

        // Audit log: Email verification resent
        $auditService = app(AuditService::class);
        $auditService->logUserActivity($user, 'email_verification_resent', [
            'email' => $user->email,
        ]);
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout 
        :heading="$is_initial_setup ? __('Complete Your Profile') : __('Profile Settings')" 
        :subheading="$is_initial_setup ? __('Let\'s set up your account to get started with KeNHAVATE') : __('Manage your personal information and preferences')">
        
        {{-- Progress Indicator for Initial Setup --}}
        @if($is_initial_setup)
            <div class="mb-8 bg-gradient-to-r from-[#F8EBD5] to-white dark:from-zinc-800 dark:to-zinc-800/50 rounded-xl border border-[#FFF200] dark:border-yellow-400 p-6 shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0 w-12 h-12 bg-[#FFF200] dark:bg-yellow-400 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-[#231F20] dark:text-zinc-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-[#231F20] dark:text-white">
                            {{ __('Almost There!') }}
                        </h3>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
                            {{ __('Complete your profile to unlock all features and start innovating.') }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <form wire:submit="updateProfileInformation" class="space-y-6">
            
            {{-- Account Information Card --}}
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm overflow-hidden transition-all duration-200 hover:shadow-md">
                <div class="bg-gradient-to-r from-zinc-50 to-white dark:from-zinc-800 dark:to-zinc-800/50 px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-[#FFF200] dark:bg-yellow-400 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-[#231F20] dark:text-zinc-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-[#231F20] dark:text-white">
                                {{ __('Account Information') }}
                            </h3>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ __('Your login credentials and primary contact') }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <flux:input 
                                wire:model="username"
                                :label="__('Username')"
                                type="text" 
                                required 
                                autofocus 
                                autocomplete="username"
                                class="transition-all duration-200 focus:ring-2 focus:ring-[#FFF200] dark:focus:ring-yellow-400"
                            />
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                                {{ __('4-15 characters, letters, numbers, dots, and underscores') }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <flux:input 
                                wire:model="email"
                                :label="__('Email Address')"
                                :readonly="!empty(auth()->user()->google_id) || auth()->user()->hasVerifiedEmail()"
                                :disabled="!empty(auth()->user()->google_id) || auth()->user()->hasVerifiedEmail()"
                                type="email" 
                                required 
                                autocomplete="email"
                            />
                            @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !auth()->user()->hasVerifiedEmail())
                                <div class="flex items-start gap-2 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg">
                                    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    <div class="flex-1">
                                        <p class="text-xs text-amber-800 dark:text-amber-200 font-medium">
                                            {{ __('Your email address is unverified.') }}
                                        </p>
                                        <button 
                                            type="button"
                                            wire:click.prevent="resendVerificationNotification"
                                            class="text-xs text-amber-700 dark:text-amber-300 underline hover:no-underline mt-1">
                                            {{ __('Click here to re-send the verification email.') }}
                                        </button>
                                    </div>
                                </div>

                                @if (session('status') === 'verification-link-sent')
                                    <div class="flex items-center gap-2 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg">
                                        <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                        <p class="text-xs text-green-800 dark:text-green-200 font-medium">
                                            {{ __('A new verification link has been sent to your email address.') }}
                                        </p>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Personal Information Card --}}
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm overflow-hidden transition-all duration-200 hover:shadow-md">
                <div class="bg-gradient-to-r from-zinc-50 to-white dark:from-zinc-800 dark:to-zinc-800/50 px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-[#FFF200] dark:bg-yellow-400 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-[#231F20] dark:text-zinc-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-[#231F20] dark:text-white">
                                {{ __('Personal Information') }}
                            </h3>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ __('Tell us about yourself') }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <flux:input
                            wire:model="first_name"
                            :label="__('First Name')"
                            :required="$is_initial_setup"
                            class="transition-all duration-200 focus:ring-2 focus:ring-[#FFF200] dark:focus:ring-yellow-400"
                        />

                        <flux:input
                            wire:model="other_names"
                            :label="__('Other Names')"
                            :required="$is_initial_setup"
                            class="transition-all duration-200 focus:ring-2 focus:ring-[#FFF200] dark:focus:ring-yellow-400"
                        />

                        <flux:select
                            wire:model="gender"
                            :label="__('Gender')"
                            :required="$is_initial_setup"
                            placeholder="Select gender"
                            class="transition-all duration-200 focus:ring-2 focus:ring-[#FFF200] dark:focus:ring-yellow-400"
                        >
                            @foreach(config('kenhavate.gender_options') as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </flux:select>

                        <div class="space-y-2">
                            <flux:input
                                wire:model="mobile_phone"
                                :label="__('Mobile Phone')"
                                mask="(+999) 999-999999"
                                :required="$is_initial_setup"
                                placeholder="+254XXXXXXXXX"
                                class="transition-all duration-200 focus:ring-2 focus:ring-[#FFF200] dark:focus:ring-yellow-400"
                            />
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                                {{ __('Format: +254XXXXXXXXX (e.g., +254712345678)') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Password Setup (Initial Setup Only) --}}
            @if($is_initial_setup)
                <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm overflow-hidden transition-all duration-200 hover:shadow-md">
                    <div class="bg-gradient-to-r from-zinc-50 to-white dark:from-zinc-800 dark:to-zinc-800/50 px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-[#FFF200] dark:bg-yellow-400 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-[#231F20] dark:text-zinc-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-[#231F20] dark:text-white">
                                    {{ __('Set Password') }}
                                </h3>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ __('Create a secure password for your account') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <flux:input
                                    wire:model="password"
                                    :label="__('Password')"
                                    type="password"
                                    :required="$is_initial_setup"
                                    autocomplete="new-password"
                                    :placeholder="__('Password')"
                                    viewable
                                    class="transition-all duration-200 focus:ring-2 focus:ring-[#FFF200] dark:focus:ring-yellow-400"
                                />
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                    </svg>
                                    {{ __('Minimum 8 characters') }}
                                </p>
                            </div>

                            <flux:input
                                wire:model="password_confirmation"
                                :label="__('Confirm Password')"
                                type="password"
                                :required="$is_initial_setup"
                                autocomplete="new-password"
                                :placeholder="__('Confirm Password')"
                                viewable
                                class="transition-all duration-200 focus:ring-2 focus:ring-[#FFF200] dark:focus:ring-yellow-400"
                            />
                        </div>
                    </div>
                </div>
            @endif

            {{-- Staff Status Checkbox (Only for non-kenha email users) --}}
            @if(!str_ends_with(auth()->user()->email, '@kenha.co.ke'))
                <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm overflow-hidden transition-all duration-200 hover:shadow-md">
                    <div class="bg-gradient-to-r from-zinc-50 to-white dark:from-zinc-800 dark:to-zinc-800/50 px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-[#FFF200] dark:bg-yellow-400 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-[#231F20] dark:text-zinc-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-[#231F20] dark:text-white">
                                    {{ __('Staff Status') }}
                                </h3>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ __('Please indicate if you are a KeNHA staff member') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 space-y-4">
                        <label class="inline-flex items-center cursor-pointer">
                            <flux:checkbox wire:model.live="wants_to_be_staff" />
                            <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">
                                {{ __('I am a KeNHA staff member but I don\'t have a @kenha.co.ke email') }}
                            </span>
                        </label>
                        
                        @if($wants_to_be_staff)
                            <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg">
                                <div class="flex items-start gap-3">
                                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                    </svg>
                                    <div class="flex-1">
                                        <p class="text-sm text-blue-800 dark:text-blue-200 font-medium">
                                            {{ __('Supervisor Approval Required') }}
                                        </p>
                                        <p class="text-xs text-blue-700 dark:text-blue-300 mt-1">
                                            {{ __('Your supervisor will need to approve your staff status before you can access staff-only features.') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- KeNHA Staff Information (For users with @kenha.co.ke email) --}}
            @if($is_kenha_staff || str_ends_with(auth()->user()->email, '@kenha.co.ke'))
                <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm overflow-hidden transition-all duration-200 hover:shadow-md">
                    <div class="bg-gradient-to-r from-zinc-50 to-white dark:from-zinc-800 dark:to-zinc-800/50 px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-[#FFF200] dark:bg-yellow-400 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-[#231F20] dark:text-zinc-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-[#231F20] dark:text-white">
                                    {{ __('KeNHA Staff Information') }}
                                </h3>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ __('Your official employment details') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            <flux:input
                                wire:model="staff_number"
                                :label="__('Staff Number')"
                                :required="$is_initial_setup"
                                class="transition-all duration-200 focus:ring-2 focus:ring-[#FFF200] dark:focus:ring-yellow-400"
                            />

                            <flux:input
                                wire:model="personal_email"
                                :label="__('Personal Email')"
                                type="email"
                                placeholder="Optional"
                                class="transition-all duration-200 focus:ring-2 focus:ring-[#FFF200] dark:focus:ring-yellow-400"
                            />

                            <flux:input
                                wire:model="job_title"
                                :label="__('Job Title/Designation')"
                                :required="$is_initial_setup"
                                class="transition-all duration-200 focus:ring-2 focus:ring-[#FFF200] dark:focus:ring-yellow-400"
                            />

                            <flux:select
                                wire:model="employment_type"
                                :label="__('Employment Type')"
                                :required="$is_initial_setup"
                                placeholder="Select employment type"
                                class="transition-all duration-200 focus:ring-2 focus:ring-[#FFF200] dark:focus:ring-yellow-400"
                            >
                                @foreach(config('kenhavate.employment_types') as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </flux:select>
                        </div>
                        <div class="grid grid-cols-1 gap-4">
                            <flux:select
                                wire:model="department_id"
                                :label="__('Department')"
                                :required="$is_initial_setup"
                                placeholder="Select department"
                                class="transition-all duration-200 focus:ring-2 focus:ring-[#FFF200] dark:focus:ring-yellow-400"
                            >
                                @foreach($departments as $department)
                                    <option value="{{ $department['id'] }}">
                                        {{ $department['name'] }} ({{ $department['directorate']['name'] }} - {{ $department['directorate']['region']['name'] }})
                                    </option>
                                @endforeach
                            </flux:select>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Non-KeNHA Staff Information (For users without @kenha.co.ke who checked the box) --}}
            @if($is_other_type_staff && $wants_to_be_staff && !str_ends_with(auth()->user()->email, '@kenha.co.ke'))
                <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm overflow-hidden transition-all duration-200 hover:shadow-md">
                    <div class="bg-gradient-to-r from-zinc-50 to-white dark:from-zinc-800 dark:to-zinc-800/50 px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-[#FFF200] dark:bg-yellow-400 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-[#231F20] dark:text-zinc-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-[#231F20] dark:text-white">
                                    {{ __('Employment Information') }}
                                </h3>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ __('Connect with your supervisor') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            <flux:select
                                wire:model="employment_type"
                                :label="__('Employment Type')"
                                required
                                placeholder="Select employment type"
                                class="transition-all duration-200 focus:ring-2 focus:ring-[#FFF200] dark:focus:ring-yellow-400"
                            >
                                @foreach(config('kenhavate.employment_types') as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </flux:select>

                            <div class="space-y-2">
                                <flux:input
                                    wire:model="supervisor_email"
                                    :label="__('Supervisor\'s Email')"
                                    type="email"
                                    required
                                    placeholder="supervisor@kenha.co.ke"
                                    class="transition-all duration-200 focus:ring-2 focus:ring-[#FFF200] dark:focus:ring-yellow-400"
                                />
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                    </svg>
                                    {{ __('Must be a @kenha.co.ke email address') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Department Assignment for Non-KeNHA Staff --}}
                <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm overflow-hidden transition-all duration-200 hover:shadow-md">
                    <div class="bg-gradient-to-r from-zinc-50 to-white dark:from-zinc-800 dark:to-zinc-800/50 px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-[#FFF200] dark:bg-yellow-400 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-[#231F20] dark:text-zinc-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-[#231F20] dark:text-white">
                                    {{ __('Department Assignment') }}
                                </h3>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ __('Select your organizational unit') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="p-6">
                        <flux:select
                            wire:model="department_id"
                            :label="__('Department')"
                            required
                            placeholder="Select your department"
                            class="transition-all duration-200 focus:ring-2 focus:ring-[#FFF200] dark:focus:ring-yellow-400"
                        >
                            @foreach($regions as $region)
                                <optgroup label="{{ $region->name }}">
                                    @foreach($region->directorates as $directorate)
                                        @foreach($directorate->departments as $department)
                                            <option value="{{ $department->id }}">
                                                {{ $directorate->name }} → {{ $department->name }}
                                            </option>
                                        @endforeach
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </flux:select>
                    </div>
                </div>
            @endif

            {{-- Action Buttons --}}
            <div class="flex flex-col sm:flex-row items-center gap-4 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                <div class="flex-1 w-full sm:w-auto">
                    <flux:button 
                        icon="check-badge"
                        variant="primary" 
                        type="submit" 
                        class="w-full sm:w-auto justify-center rounded-lg bg-[#FFF200] dark:bg-yellow-400 px-6 py-3 text-sm font-semibold text-[#231F20] dark:text-zinc-900 shadow-lg hover:bg-[#FFF200]/90 dark:hover:bg-yellow-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#FFF200] dark:focus-visible:outline-yellow-400 transition-all duration-200 hover:shadow-xl hover:scale-105" 
                        data-test="update-profile-button"
                    >
                        {{ $is_initial_setup ? __('Complete Profile & Continue') : __('Save Changes') }}
                    </flux:button>
                </div>

                <x-action-message class="text-sm" on="profile-updated">
                    <div class="flex items-center gap-2 text-green-600 dark:text-green-400 font-medium">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        {{ $is_initial_setup ? __('Profile completed successfully!') : __('Changes saved successfully!') }}
                    </div>
                </x-action-message>
            </div>
        </form>

        {{-- Delete Account Section - Only show if not initial setup --}}
        @if(!$is_initial_setup)
            <div class="mt-12 pt-8 border-t border-zinc-200 dark:border-zinc-700">
                <livewire:settings.delete-user-form />
            </div>
        @endif

    </x-settings.layout>

    {{-- Custom Styles for Enhanced UI --}}
    <style>
        /* Smooth transitions for all interactive elements */
        input, select, textarea {
            transition: all 0.2s ease-in-out;
        }

        /* Enhanced focus states */
        input:focus, select:focus, textarea:focus {
            transform: translateY(-1px);
        }

        /* Card hover effects */
        .bg-white:hover, .dark .dark\:bg-zinc-800:hover {
            transform: translateY(-2px);
        }

        /* Button press effect */
        button:active {
            transform: scale(0.98);
        }

        /* Smooth scroll */
        html {
            scroll-behavior: smooth;
        }

        /* Custom select arrow */
        select {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }

        /* Animated gradient background for cards */
        @keyframes gradient-shift {
            0%, 100% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
        }

        .bg-gradient-to-r {
            background-size: 200% 200%;
            animation: gradient-shift 15s ease infinite;
        }

        /* Loading state animation */
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }

        [wire\:loading] {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        /* Enhanced tooltip styling */
        [title] {
            position: relative;
            cursor: help;
        }

        /* Success message animation */
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        x-action-message > div {
            animation: slideInRight 0.3s ease-out;
        }

        /* Enhanced error message styling */
        .text-red-600, .dark .dark\:text-red-400 {
            font-weight: 500;
        }

        /* Responsive font scaling */
        @media (max-width: 640px) {
            h3 {
                font-size: 1rem;
            }
            
            p {
                font-size: 0.875rem;
            }
        }

        /* Dark mode specific enhancements */
        @media (prefers-color-scheme: dark) {
            input:focus, select:focus, textarea:focus {
                box-shadow: 0 0 0 3px rgba(255, 242, 0, 0.1);
            }
        }

        /* Print styles */
        @media print {
            .no-print {
                display: none !important;
            }
            
            .bg-white {
                box-shadow: none !important;
                border: 1px solid #e5e7eb !important;
            }
        }

        /* Accessibility improvements */
        *:focus-visible {
            outline: 2px solid #FFF200;
            outline-offset: 2px;
        }

        /* Reduced motion for users who prefer it */
        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* Enhanced placeholder styling */
        ::placeholder {
            color: #9ca3af;
            opacity: 0.6;
        }

        .dark ::placeholder {
            color: #6b7280;
            opacity: 0.5;
        }

        /* Input group styling for better visual hierarchy */
        .space-y-2 > p {
            margin-top: 0.25rem;
        }

        /* Enhanced optgroup styling */
        optgroup {
            font-weight: 600;
            color: #374151;
        }

        .dark optgroup {
            color: #d1d5db;
        }

        option {
            padding: 0.5rem;
        }

        /* Card shadow on focus-within */
        .bg-white:focus-within, .dark .dark\:bg-zinc-800:focus-within {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        /* Icon animations */
        svg {
            transition: transform 0.2s ease;
        }

        .group:hover svg {
            transform: scale(1.1);
        }

        /* Badge styling for required fields */
        label:has(+ input[required])::after,
        label:has(+ select[required])::after {
            content: " *";
            color: #ef4444;
            font-weight: bold;
        }

        /* Enhanced divider styling */
        .border-t {
            position: relative;
        }

        .border-t::before {
            content: "";
            position: absolute;
            top: -1px;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, #e5e7eb 50%, transparent);
        }

        .dark .border-t::before {
            background: linear-gradient(90deg, transparent, #3f3f46 50%, transparent);
        }
    </style>

    {{-- JavaScript for enhanced interactivity --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-save draft functionality (optional)
            let saveTimeout;
            const formInputs = document.querySelectorAll('input, select, textarea');
            
            formInputs.forEach(input => {
                input.addEventListener('input', function() {
                    clearTimeout(saveTimeout);
                    saveTimeout = setTimeout(() => {
                        // Optionally auto-save draft to localStorage
                        console.log('Auto-saving draft...');
                    }, 2000);
                });
            });

            // Phone number formatting
            const phoneInput = document.querySelector('input[type="tel"]');
            if (phoneInput) {
                phoneInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.startsWith('254')) {
                        e.target.value = '+' + value;
                    } else if (value.startsWith('0')) {
                        e.target.value = '+254' + value.substring(1);
                    }
                });
            }

            // Form validation feedback
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function() {
                    const submitButton = form.querySelector('button[type="submit"]');
                    if (submitButton) {
                        submitButton.disabled = true;
                        submitButton.innerHTML = '<svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Processing...';
                    }
                });
            }

            // Smooth scroll to first error
            window.addEventListener('livewire:init', () => {
                Livewire.on('validation-error', () => {
                    setTimeout(() => {
                        // Look for Flux error messages or any error text
                        const firstError = document.querySelector('.text-red-600, .text-red-400, [class*="error"], .invalid-feedback');
                        if (firstError) {
                            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            return;
                        }
                        
                        // Fallback: look for any element with error styling
                        const errorElement = document.querySelector('[aria-invalid="true"], .border-red-500, .ring-red-500');
                        if (errorElement) {
                            errorElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    }, 100);
                });
            });

            // Character counter for text inputs
            const textInputs = document.querySelectorAll('input[type="text"], input[type="email"]');
            textInputs.forEach(input => {
                const maxLength = input.getAttribute('maxlength');
                if (maxLength) {
                    const counter = document.createElement('span');
                    counter.className = 'text-xs text-zinc-400 dark:text-zinc-500 mt-1';
                    counter.textContent = `0/${maxLength}`;
                    
                    input.addEventListener('input', function() {
                        counter.textContent = `${this.value.length}/${maxLength}`;
                    });
                    
                    input.parentElement.appendChild(counter);
                }
            });
        });
    </script>
</section>