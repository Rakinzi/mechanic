<?php

namespace App\Policies;

use App\Models\JobCard;
use App\Models\User;

class JobCardPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'technician', 'client']);
    }

    public function view(User $user, JobCard $jobCard): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('technician')) {
            return $jobCard->jobStages()->assignedToTechnician($user)->exists();
        }

        if ($user->hasRole('client')) {
            return $jobCard->vehicle->client?->user_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, JobCard $jobCard): bool
    {
        return $user->hasRole('admin') && $jobCard->status !== 'COMPLETED';
    }

    public function close(User $user, JobCard $jobCard): bool
    {
        return $user->hasRole('admin') && $jobCard->status !== 'COMPLETED';
    }

    public function downloadSummary(User $user, JobCard $jobCard): bool
    {
        return $this->view($user, $jobCard);
    }
}
