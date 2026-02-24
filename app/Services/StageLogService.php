<?php

namespace App\Services;

use App\Models\JobStage;
use App\Models\User;

class StageLogService
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function record(
        JobStage $jobStage,
        string $action,
        ?string $fromStatus,
        ?string $toStatus,
        ?User $actor,
        ?array $metadata = null,
    ): void {
        $jobStage->logs()->create([
            'actor_id' => $actor?->id,
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'metadata' => $metadata,
            'happened_at' => now(),
        ]);
    }
}
