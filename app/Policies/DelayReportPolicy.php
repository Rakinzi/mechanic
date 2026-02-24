<?php

namespace App\Policies;

use App\Enums\DelayReportStatus;
use App\Models\DelayReport;
use App\Models\User;

class DelayReportPolicy
{
    public function view(User $user, DelayReport $delayReport): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('mechanic')) {
            return $delayReport->submitted_by === $user->id;
        }

        return $delayReport->jobStage->jobCard->vehicle->client?->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('mechanic');
    }

    public function review(User $user, DelayReport $delayReport): bool
    {
        return $user->hasRole('admin') && $delayReport->status === DelayReportStatus::Pending;
    }

    public function approve(User $user, DelayReport $delayReport): bool
    {
        return $this->review($user, $delayReport);
    }

    public function reject(User $user, DelayReport $delayReport): bool
    {
        return $this->review($user, $delayReport);
    }
}
