<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /**
     * Create a new user.
     */
    public function createUser(array $data): User
    {
        return User::create([
            'name' => $data['name'] ?? '',
            'email' => $data['email'],
            'password' => isset($data['password']) ? Hash::make($data['password']) : null,
            'account_status' => $data['account_status'] ?? 'active',
            'points' => $data['points'] ?? 0,
        ]);
    }

    /**
     * Update user profile.
     */
    public function updateProfile(User $user, array $data): User
    {
        $user->update($data);
        return $user->fresh();
    }

    /**
     * Create staff profile for user.
     */
    public function createStaffProfile(User $user, array $data): Staff
    {
        return $user->staff()->create([
            'first_name' => $data['first_name'],
            'other_names' => $data['other_names'],
            'password_hash' => Hash::make($data['password']),
            'gender' => $data['gender'],
            'mobile_phone' => $data['mobile_phone'],
            'staff_number' => $data['staff_number'] ?? null,
            'personal_email' => $data['personal_email'] ?? null,
            'job_title' => $data['job_title'] ?? null,
            'department_id' => $data['department_id'],
            'employment_type' => $data['employment_type'] ?? null,
            'supervisor_id' => $data['supervisor_id'] ?? null,
        ]);
    }

    /**
     * Update staff profile.
     */
    public function updateStaffProfile(Staff $staff, array $data): Staff
    {
        $staff->update($data);
        return $staff->fresh();
    }

    /**
     * Accept terms and conditions.
     */
    public function acceptTerms(User $user, string $version = null): void
    {
        $user->update([
            'terms_accepted_count' => $user->terms_accepted_count + 1,
            'last_terms_accepted_at' => now(),
            'current_terms_version' => $version,
        ]);
    }

    /**
     * Change user account status.
     */
    public function changeAccountStatus(User $user, string $status): void
    {
        $user->update(['account_status' => $status]);
    }

    /**
     * Check if user can access the system.
     */
    public function canAccessSystem(User $user): bool
    {
        return $user->isActive() && $user->hasAcceptedTerms();
    }

    /**
     * Get user with staff profile.
     */
    public function getUserWithStaff(int $userId): ?User
    {
        return User::with('staff')->find($userId);
    }
}