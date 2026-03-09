<?php

namespace App\Services;

use App\Models\JobCard;
use App\Models\JobStage;
use App\Models\User;

class JobCardAuditService
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function record(
        JobCard $jobCard,
        string $event,
        string $description,
        ?User $actor = null,
        ?JobStage $jobStage = null,
        ?array $metadata = null,
    ): void {
        $jobCard->audits()->create([
            'job_stage_id' => $jobStage?->id,
            'actor_id' => $actor?->id,
            'event' => $event,
            'description' => $description,
            'metadata' => $metadata,
            'happened_at' => now(),
        ]);
    }
}
