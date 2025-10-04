<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'first_name',
        'other_names',
        'password_hash',
        'gender',
        'mobile_phone',
        'staff_number',
        'personal_email',
        'personal_email_verified_at',
        'job_title',
        'department_id',
        'employment_type',
        'supervisor_id',
        'supervisor_approved_at',
    ];

    protected $casts = [
        'personal_email_verified_at' => 'datetime',
        'supervisor_approved_at' => 'datetime',
    ];

    /**
     * Get the user that owns this staff profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the department for this staff member.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the supervisor for this staff member.
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * Get the directorate through the department.
     */
    public function directorate(): BelongsTo
    {
        return $this->department->directorate();
    }

    /**
     * Get the region through the department.
     */
    public function region(): BelongsTo
    {
        return $this->department->region();
    }

    /**
     * Check if the staff profile is complete.
     */
    public function isProfileComplete(): bool
    {
        return !empty($this->first_name) &&
               !empty($this->other_names) &&
               !empty($this->gender) &&
               !empty($this->mobile_phone) &&
               !empty($this->department_id);
    }

    /**
     * Check if the staff member is approved by supervisor.
     */
    public function isSupervisorApproved(): bool
    {
        return !is_null($this->supervisor_approved_at);
    }

    /**
     * Get the full name of the staff member.
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->other_names);
    }
}
