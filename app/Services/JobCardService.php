<?php

namespace App\Services;

use App\Enums\StageStatus;
use App\Models\JobCard;
use App\Models\Stage;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class JobCardService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(Vehicle $vehicle, User $creator, array $payload): JobCard
    {
        /** @var JobCard $jobCard */
        $jobCard = DB::transaction(function () use ($vehicle, $creator, $payload): JobCard {
            $jobCard = JobCard::query()->create([
                'vehicle_id' => $vehicle->id,
                'created_by' => $creator->id,
                'job_number' => Arr::get($payload, 'job_number', $this->generateJobNumber()),
                'customer_complaint' => Arr::get($payload, 'customer_complaint'),
                'diagnosis_notes' => Arr::get($payload, 'diagnosis_notes'),
                'status' => 'OPEN',
                'received_at' => Arr::get($payload, 'received_at', now()),
                'promised_delivery_at' => Arr::get($payload, 'promised_delivery_at'),
            ]);

            $mechanicAssignments = collect(Arr::get($payload, 'assigned_mechanics', []));

            Stage::query()
                ->where('is_active', true)
                ->orderBy('sequence')
                ->get()
                ->each(function (Stage $stage) use ($jobCard, $mechanicAssignments): void {
                    $jobCard->jobStages()->create([
                        'stage_id' => $stage->id,
                        'assigned_mechanic_id' => $mechanicAssignments->get((string) $stage->id),
                        'sequence' => $stage->sequence,
                        'status' => StageStatus::NotStarted,
                        'last_status_changed_at' => now(),
                    ]);
                });

            return $jobCard;
        });

        return $jobCard->load(['vehicle.client', 'jobStages.stage', 'jobStages.assignedMechanic']);
    }

    protected function generateJobNumber(): string
    {
        $count = JobCard::query()->count() + 1;

        return sprintf('JC-%s-%05d', now()->format('Ymd'), $count);
    }
}
