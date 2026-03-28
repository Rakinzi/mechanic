<?php

namespace App\Policies;

use App\Models\JobStage;
use App\Models\User;

class JobStagePolicy
{
    public function view(User $user, JobStage $jobStage): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('mechanic')) {
            return $jobStage->isAssignedToMechanic($user);
        }

        if ($user->hasRole('client')) {
            return $jobStage->jobCard->vehicle->client?->user_id === $user->id;
        }

        return false;
    }

    public function start(User $user, JobStage $jobStage): bool
    {
        return $user->hasRole('mechanic') && $jobStage->isAssignedToMechanic($user);
    }

    public function pause(User $user, JobStage $jobStage): bool
    {
        return $this->start($user, $jobStage);
    }

    public function block(User $user, JobStage $jobStage): bool
    {
        return $this->start($user, $jobStage);
    }

    public function complete(User $user, JobStage $jobStage): bool
    {
        return $this->start($user, $jobStage);
    }

    public function submitDelay(User $user, JobStage $jobStage): bool
    {
        return $this->start($user, $jobStage);
    }

    public function updatePlan(User $user, JobStage $jobStage): bool
    {
        return $user->hasRole('admin') && $jobStage->status === \App\Enums\StageStatus::NotStarted;
    }
}
