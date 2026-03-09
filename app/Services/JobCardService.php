<?php

namespace App\Services;

use App\Enums\StageStatus;
use App\Models\JobCard;
use App\Models\JobStage;
use App\Models\Stage;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class JobCardService
{
    public function __construct(protected JobCardAuditService $jobCardAuditService) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(Vehicle $vehicle, User $creator, array $payload): JobCard
    {
        /** @var JobCard $jobCard */
        $jobCard = DB::transaction(function () use ($vehicle, $creator, $payload): JobCard {
            /** @var Collection<int, array<string, mixed>> $selectedStages */
            $selectedStages = collect(Arr::get($payload, 'selected_stages', []));
            $receivedAt = Arr::get($payload, 'received_at', now());

            if ($selectedStages->isEmpty()) {
                throw new RuntimeException('At least one stage must be selected for a job card.');
            }

            $jobCard = JobCard::query()->create([
                'vehicle_id' => $vehicle->id,
                'created_by' => $creator->id,
                'job_number' => Arr::get($payload, 'job_number', $this->generateJobNumber()),
                'customer_complaint' => Arr::get($payload, 'customer_complaint'),
                'diagnosis_notes' => Arr::get($payload, 'diagnosis_notes'),
                'status' => 'OPEN',
                'received_at' => $receivedAt,
                'promised_delivery_at' => Arr::get($payload, 'promised_delivery_at'),
            ]);

            $stageTemplates = Stage::query()
                ->whereIn('id', $selectedStages->pluck('stage_id'))
                ->get()
                ->keyBy('id');

            $selectedStages
                ->sortBy(fn (array $selectedStage): int => (int) $stageTemplates->get($selectedStage['stage_id'])?->sequence)
                ->values()
                ->each(function (array $selectedStage, int $index) use ($jobCard, $stageTemplates, $receivedAt): void {
                    /** @var Stage $stage */
                    $stage = $stageTemplates->get($selectedStage['stage_id']);

                    $jobCard->jobStages()->create([
                        'stage_id' => $stage->id,
                        'assigned_mechanic_id' => $selectedStage['assigned_mechanic_id'] ?? null,
                        'sequence' => $index + 1,
                        'planned_duration_value' => $selectedStage['planned_duration_value'],
                        'planned_duration_unit' => $selectedStage['planned_duration_unit'],
                        'status' => StageStatus::NotStarted,
                        'planned_due_at' => $this->calculatePlannedDueAt($receivedAt, $selectedStage['planned_duration_value'], $selectedStage['planned_duration_unit']),
                        'last_status_changed_at' => now(),
                    ]);
                });

            $jobCard->update([
                'current_job_stage_id' => $jobCard->jobStages()->orderBy('sequence')->value('id'),
            ]);

            $this->jobCardAuditService->record(
                $jobCard,
                'JOB_CARD_CREATED',
                'Job card created with selected repair stages.',
                $creator,
                null,
                [
                    'selected_stage_ids' => $jobCard->jobStages()->orderBy('sequence')->pluck('stage_id')->all(),
                    'promised_delivery_at' => $jobCard->promised_delivery_at?->toDateTimeString(),
                ],
            );

            return $jobCard;
        });

        return $jobCard->load(['vehicle.client', 'currentJobStage.stage', 'jobStages.stage', 'jobStages.assignedMechanic']);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateFutureStage(JobStage $jobStage, array $payload, User $actor): JobStage
    {
        if ($jobStage->status !== StageStatus::NotStarted) {
            throw new RuntimeException('Only future stages can be edited.');
        }

        $jobStage->forceFill([
            'assigned_mechanic_id' => Arr::get($payload, 'assigned_mechanic_id'),
            'planned_duration_value' => Arr::get($payload, 'planned_duration_value'),
            'planned_duration_unit' => Arr::get($payload, 'planned_duration_unit'),
            'planned_due_at' => $this->calculatePlannedDueAt(
                $jobStage->jobCard->received_at ?? now(),
                (int) Arr::get($payload, 'planned_duration_value'),
                (string) Arr::get($payload, 'planned_duration_unit'),
            ),
            'latest_note' => Arr::get($payload, 'latest_note'),
            'last_status_changed_at' => now(),
        ])->save();

        $this->jobCardAuditService->record(
            $jobStage->jobCard,
            'FUTURE_STAGE_UPDATED',
            sprintf('Future stage plan updated for %s.', $jobStage->stage->name),
            $actor,
            $jobStage,
            [
                'assigned_mechanic_id' => $jobStage->assigned_mechanic_id,
                'planned_duration_value' => $jobStage->planned_duration_value,
                'planned_duration_unit' => $jobStage->planned_duration_unit,
                'planned_due_at' => $jobStage->planned_due_at?->toDateTimeString(),
                'latest_note' => $jobStage->latest_note,
            ],
        );

        return $jobStage->fresh(['stage', 'assignedMechanic']);
    }

    protected function calculatePlannedDueAt(Carbon|string $baseTime, int $value, string $unit): Carbon
    {
        $timestamp = $baseTime instanceof Carbon ? $baseTime->copy() : Carbon::parse($baseTime);

        if ($unit === 'days') {
            return $timestamp->addDays($value);
        }

        return $timestamp->addHours($value);
    }

    protected function generateJobNumber(): string
    {
        $count = JobCard::query()->count() + 1;

        return sprintf('JC-%s-%05d', now()->format('Ymd'), $count);
    }
}
