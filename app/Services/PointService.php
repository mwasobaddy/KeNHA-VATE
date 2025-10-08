<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PointTransaction;
use App\Models\User;

class PointService
{
    public function __construct(
        private readonly AuditService $auditService
    ) {}
    /**
     * Award points to a user.
     */
    public function awardPoints(
        User $user,
        int $points,
        string $description,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): void {
        // Create transaction record
        $transaction = PointTransaction::create([
            'user_id' => $user->id,
            'points' => $points,
            'transaction_type' => 'earned',
            'description' => $description,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
        ]);

        // Update user's total points
        $user->increment('points', $points);

        // Audit log: Points awarded
        $this->auditService->logUserActivity($user, 'points_awarded', [
            'points' => $points,
            'description' => $description,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'transaction_id' => $transaction->id,
            'new_balance' => $user->fresh()->points,
        ]);

        // Send notification
        app(NotificationService::class)->success(
            $user,
            'Points Awarded',
            "You earned {$points} points for {$description}"
        );
    }

    /**
     * Redeem points from a user.
     */
    public function redeemPoints(User $user, int $points, string $description): bool
    {
        if ($user->points < $points) {
            return false;
        }

        // Create transaction record
        $transaction = PointTransaction::create([
            'user_id' => $user->id,
            'points' => -$points,
            'transaction_type' => 'redeemed',
            'description' => $description,
        ]);

        // Update user's total points
        $user->decrement('points', $points);

        // Audit log: Points redeemed
        $this->auditService->logUserActivity($user, 'points_redeemed', [
            'points' => $points,
            'description' => $description,
            'transaction_id' => $transaction->id,
            'new_balance' => $user->fresh()->points,
        ]);

        return true;
    }

    /**
     * Get first login points amount.
     */
    public function getFirstLoginPoints(): int
    {
        return config('kenhavate.points.first_login', 50);
    }

    /**
     * Get daily login points amount.
     */
    public function getDailyLoginPoints(): int
    {
        return config('kenhavate.points.daily_login', 10);
    }

    /**
     * Check if user has received first login bonus.
     */
    public function hasReceivedFirstLoginBonus(User $user): bool
    {
        return PointTransaction::where('user_id', $user->id)
            ->where('description', 'First login bonus')
            ->exists();
    }

    /**
     * Get user's point balance.
     */
    public function getBalance(User $user): int
    {
        return $user->points;
    }

    /**
     * Get user's point transaction history.
     */
    public function getTransactionHistory(User $user, int $limit = 50)
    {
        return $user->pointTransactions()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}