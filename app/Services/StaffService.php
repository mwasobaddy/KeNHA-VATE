<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Staff;
use App\Models\User;

class StaffService
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly AuditService $auditService
    ) {}

    /**
     * Request supervisor approval for staff member.
     */
    public function requestSupervisorApproval(Staff $staff): void
    {
        $supervisor = $staff->supervisor;

        if (!$supervisor) {
            // Send email notification only if supervisor doesn't exist in system
            // TODO: Implement email notification
            $this->auditService->log('supervisor_approval_requested_email', $staff->user_id, [
                'supervisor_email' => $staff->supervisor->email ?? 'unknown',
            ]);
            return;
        }

        // Send in-app notification to supervisor
        $this->notificationService->info(
            $supervisor,
            'Staff Approval Request',
            "{$staff->getFullNameAttribute()} has requested approval as KeNHA staff.",
            route('staff.approvals.show', $staff->id)
        );

        $this->auditService->log('supervisor_approval_requested', $staff->user_id, [
            'supervisor_id' => $supervisor->id,
        ]);
    }

    /**
     * Approve staff member by supervisor.
     */
    public function approveBySupervisor(Staff $staff, User $supervisor): void
    {
        $staff->update(['supervisor_approved_at' => now()]);

        // Notify the staff member
        $this->notificationService->success(
            $staff->user,
            'Staff Approval Granted',
            'Your KeNHA staff status has been approved by your supervisor.'
        );

        $this->auditService->log('staff_approved', $staff->user_id, [
            'supervisor_id' => $supervisor->id,
        ]);
    }

    /**
     * Reject staff member approval.
     */
    public function rejectBySupervisor(Staff $staff, User $supervisor, string $reason = null): void
    {
        // Notify the staff member
        $message = 'Your KeNHA staff approval request has been rejected';
        if ($reason) {
            $message .= ": {$reason}";
        }

        $this->notificationService->warning(
            $staff->user,
            'Staff Approval Rejected',
            $message
        );

        $this->auditService->log('staff_rejected', $staff->user_id, [
            'supervisor_id' => $supervisor->id,
            'reason' => $reason,
        ]);
    }

    /**
     * Get staff members pending supervisor approval.
     */
    public function getPendingApprovals(User $supervisor)
    {
        return Staff::where('supervisor_id', $supervisor->id)
            ->whereNull('supervisor_approved_at')
            ->with('user')
            ->get();
    }

    /**
     * Check if staff member is eligible for approval.
     */
    public function isEligibleForApproval(Staff $staff): bool
    {
        return !$staff->isSupervisorApproved() &&
               !empty($staff->supervisor_id) &&
               $staff->isProfileComplete();
    }
}