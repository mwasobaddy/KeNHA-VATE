<?php

use App\Models\Department;
use App\Models\Region;
use App\Services\StaffService;
use App\Services\UserService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public string $first_name = '';
    public string $other_names = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $gender = '';
    public string $mobile_phone = '';
    public ?string $staff_number = null;
    public ?string $personal_email = null;
    public ?string $job_title = null;
    public ?int $department_id = null;
    public ?string $employment_type = null;
    public ?string $supervisor_email = null;
    public bool $is_kenha_staff = false;

    public $regions = [];
    public $departments = [];

    public function mount(): void
    {
        $user = Auth::user();

        // Check if user is KeNHA staff based on email domain
        $this->is_kenha_staff = $user->isKenhaStaff();

        // Load regions for dropdown
        $this->regions = Region::active()->with('directorates.departments')->get();
    }

    /**
     * Complete the user profile.
     */
    public function completeProfile(): void
    {
        $user = Auth::user();
        $rules = $this->getValidationRules($user);

        $this->validate($rules);

                // Create staff profile
        $userService = app(UserService::class);
        $staffData = [
            'staff_number' => $data['staff_number'] ?? null,
            'personal_email' => $data['personal_email'] ?? null,
            'job_title' => $data['job_title'] ?? null,
            'department_id' => $data['department_id'],
            'employment_type' => $data['employment_type'] ?? null,
        ];

        if ($this->is_kenha_staff) {
            $staffData = array_merge($staffData, [
                'staff_number' => $this->staff_number,
                'personal_email' => $this->personal_email,
                'job_title' => $this->job_title,
                'employment_type' => $this->employment_type,
            ]);
        } else {
            $staffData['employment_type'] = $this->employment_type;
            $staffData['supervisor_email'] = $this->supervisor_email;
        }

        $userService->createStaffProfile($user, $staffData);

        // If not KeNHA staff, request supervisor approval
        if (!$this->is_kenha_staff && $this->supervisor_email) {
            $staffService = app(StaffService::class);
            $staffService->requestSupervisorApproval($user->staff);
        }

        // Fire profile completed event
        \App\Events\ProfileCompleted::dispatch($user);

        // Redirect to terms
        $this->redirect(route('terms.show'), navigate: true);
    }

    /**
     * Get validation rules based on user type.
     */
    protected function getValidationRules($user): array
    {
        $rules = [
            'first_name' => 'required|string|max:100',
            'other_names' => 'required|string|max:100',
            'password' => 'required|string|min:8|confirmed',
            'gender' => ['required', Rule::in(array_keys(config('kenhavate.gender_options')))],
            'mobile_phone' => 'required|string|regex:/^\+?[1-9]\d{1,14}$/',
            'department_id' => 'required|exists:departments,id',
        ];

        if ($this->is_kenha_staff) {
            $rules = array_merge($rules, [
                'staff_number' => 'required|string|unique:staff,staff_number',
                'personal_email' => 'nullable|email|different:email',
                'job_title' => 'required|string|max:150',
                'employment_type' => ['required', Rule::in(array_keys(config('kenhavate.employment_types')))],
            ]);
        } else {
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
}; ?>

<div class="max-w-2xl mx-auto space-y-6">
    <div class="text-center">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
            {{ __('Complete Your Profile') }}
        </h1>
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
            {{ __('Please provide your information to complete your account setup.') }}
        </p>
    </div>

    <form wire:submit="completeProfile" class="space-y-6">
        <!-- Basic Information -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 mb-4">
                {{ __('Basic Information') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:input
                    wire:model="first_name"
                    :label="__('First Name')"
                    required
                    autofocus
                />

                <flux:input
                    wire:model="other_names"
                    :label="__('Other Names')"
                    required
                />

                <flux:select
                    wire:model="gender"
                    :label="__('Gender')"
                    required
                    placeholder="{{ __('Select gender') }}"
                >
                    @foreach(config('kenhavate.gender_options') as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </flux:select>

                <flux:input
                    wire:model="mobile_phone"
                    :label="__('Mobile Phone')"
                    type="tel"
                    required
                    placeholder="+254XXXXXXXXX"
                />
            </div>
        </div>

        <!-- Password -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 mb-4">
                {{ __('Account Security') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:input
                    wire:model="password"
                    :label="__('Password')"
                    type="password"
                    required
                    viewable
                />

                <flux:input
                    wire:model="password_confirmation"
                    :label="__('Confirm Password')"
                    type="password"
                    required
                    viewable
                />
            </div>
        </div>

        @if($is_kenha_staff)
            <!-- KeNHA Staff Information -->
            <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 mb-4">
                    {{ __('KeNHA Staff Information') }}
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input
                        wire:model="staff_number"
                        :label="__('Staff Number')"
                        required
                    />

                    <flux:input
                        wire:model="personal_email"
                        :label="__('Personal Email (Optional)')"
                        type="email"
                    />

                    <flux:input
                        wire:model="job_title"
                        :label="__('Job Title/Designation')"
                        required
                    />

                    <flux:select
                        wire:model="employment_type"
                        :label="__('Employment Type')"
                        required
                        placeholder="{{ __('Select employment type') }}"
                    >
                        @foreach(config('kenhavate.employment_types') as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 mb-4">
                    {{ __('Additional Information') }}
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:select
                        wire:model="employment_type"
                        :label="__('Employment Type')"
                        required
                        placeholder="{{ __('Select employment type') }}"
                    >
                        @foreach(config('kenhavate.employment_types') as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>

                    <flux:input
                        wire:model="supervisor_email"
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
                    wire:model="department_id"
                    :label="__('Department')"
                    required
                    placeholder="{{ __('Select your department') }}"
                >
                    @foreach($regions as $region)
                        <optgroup label="{{ $region->name }}">
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

        <!-- Submit Button -->
        <div class="flex justify-end">
            <flux:button variant="primary" type="submit" class="w-full md:w-auto">
                {{ __('Complete Profile') }}
            </flux:button>
        </div>
    </form>
</div>