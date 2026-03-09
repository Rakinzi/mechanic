<?php

namespace App\Services;

use App\Enums\DelayReportStatus;
use App\Enums\StageLogAction;
use App\Enums\StageStatus;
use App\Events\StageCompleted;
use App\Events\StageMarkedOverdue;
use App\Events\StageStarted;
use App\Models\JobStage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class JobStageService
{
    public function __construct(
        protected StageLogService $stageLogService,
        protected JobCardAuditService $jobCardAuditService,
    ) {}

    public function start(JobStage $jobStage, User $actor, ?string $note = null): JobStage
    {
        if ($jobStage->status === StageStatus::Completed) {
            throw new RuntimeException('Completed stages cannot be started.');
        }

        if (! $this->isPreviousStageCompleted($jobStage)) {
            throw new RuntimeException('Previous stage must be completed first.');
        }

        $fromStatus = $jobStage->status->value;

        $jobStage = DB::transaction(function () use ($jobStage, $note): JobStage {
            $dueAt = $this->calculateDueAt($jobStage);

            $jobStage->forceFill([
                'status' => StageStatus::InProgress,
                'started_at' => now(),
                'actual_started_at' => now(),
                'paused_at' => null,
                'blocked_at' => null,
                'due_at' => $dueAt,
                'planned_due_at' => $jobStage->planned_due_at ?? $dueAt,
                'last_status_changed_at' => now(),
                'latest_note' => $note,
            ])->save();

            $jobStage->jobCard->update([
                'status' => 'IN_PROGRESS',
                'current_job_stage_id' => $jobStage->id,
            ]);

            return $jobStage->fresh(['stage', 'jobCard']);
        });

        $this->stageLogService->record($jobStage, StageLogAction::Started->value, $fromStatus, $jobStage->status->value, $actor, [
            'note' => $note,
        ]);

        $this->jobCardAuditService->record(
            $jobStage->jobCard,
            'STAGE_STARTED',
            sprintf('%s started by %s.', $jobStage->stage->name, $actor->name),
            $actor,
            $jobStage,
            ['note' => $note],
        );

        DB::afterCommit(fn (): mixed => event(new StageStarted($jobStage, $actor)));

        return $jobStage;
    }

    public function pause(JobStage $jobStage, User $actor, ?string $note = null): JobStage
    {
        $fromStatus = $jobStage->status->value;

        $jobStage->forceFill([
            'status' => StageStatus::Blocked,
            'paused_at' => now(),
            'blocked_at' => now(),
            'last_status_changed_at' => now(),
            'latest_note' => $note,
        ])->save();

        $jobStage = $jobStage->fresh();

        $this->stageLogService->record($jobStage, StageLogAction::Paused->value, $fromStatus, $jobStage->status->value, $actor, [
            'note' => $note,
        ]);

        $this->jobCardAuditService->record(
            $jobStage->jobCard,
            'STAGE_PAUSED',
            sprintf('%s paused by %s.', $jobStage->stage->name, $actor->name),
            $actor,
            $jobStage,
            ['note' => $note],
        );

        return $jobStage;
    }

    public function block(JobStage $jobStage, User $actor, ?string $note = null): JobStage
    {
        $fromStatus = $jobStage->status->value;

        $jobStage->forceFill([
            'status' => StageStatus::Blocked,
            'blocked_at' => now(),
            'last_status_changed_at' => now(),
            'latest_note' => $note,
        ])->save();

        $jobStage = $jobStage->fresh();

        $this->stageLogService->record($jobStage, StageLogAction::Blocked->value, $fromStatus, $jobStage->status->value, $actor, [
            'note' => $note,
        ]);

        $this->jobCardAuditService->record(
            $jobStage->jobCard,
            'STAGE_BLOCKED',
            sprintf('%s blocked by %s.', $jobStage->stage->name, $actor->name),
            $actor,
            $jobStage,
            ['note' => $note],
        );

        return $jobStage;
    }

    public function complete(JobStage $jobStage, User $actor, ?string $note = null): JobStage
    {
        if (! $this->isPreviousStageCompleted($jobStage)) {
            throw new RuntimeException('Previous stage must be completed first.');
        }

        $requiresApprovedDelay = $this->isOverdue($jobStage);

        if ($requiresApprovedDelay && ! $this->hasApprovedDelayReport($jobStage)) {
            throw new RuntimeException('Overdue stage requires an approved delay report before completion.');
        }

        $fromStatus = $jobStage->status->value;

        $jobStage->forceFill([
            'status' => StageStatus::Completed,
            'completed_at' => now(),
            'actual_completed_at' => now(),
            'handoff_ready_at' => now(),
            'last_status_changed_at' => now(),
            'latest_note' => $note,
        ])->save();

        $jobStage = $jobStage->fresh(['jobCard']);

        $nextStageId = $jobStage->jobCard->jobStages()
            ->where('status', '!=', StageStatus::Completed->value)
            ->orderBy('sequence')
            ->value('id');

        if ($nextStageId === null) {
            $jobStage->jobCard->update([
                'status' => 'COMPLETED',
                'current_job_stage_id' => null,
                'closed_at' => now(),
            ]);
        } else {
            $jobStage->jobCard->update([
                'status' => 'IN_PROGRESS',
                'current_job_stage_id' => $nextStageId,
            ]);
        }

        $this->stageLogService->record($jobStage, StageLogAction::Completed->value, $fromStatus, $jobStage->status->value, $actor, [
            'note' => $note,
        ]);

        $this->jobCardAuditService->record(
            $jobStage->jobCard,
            'STAGE_COMPLETED',
            sprintf('%s completed by %s.', $jobStage->stage->name, $actor->name),
            $actor,
            $jobStage,
            ['note' => $note],
        );

        DB::afterCommit(fn (): mixed => event(new StageCompleted($jobStage, $actor)));

        return $jobStage;
    }

    public function markOverdue(JobStage $jobStage): JobStage
    {
        if (! $this->isOverdue($jobStage) || $jobStage->status === StageStatus::Completed) {
            return $jobStage;
        }

        if ($jobStage->status === StageStatus::Overdue) {
            return $jobStage;
        }

        $fromStatus = $jobStage->status->value;

        $jobStage->forceFill([
            'status' => StageStatus::Overdue,
            'last_status_changed_at' => now(),
        ])->save();

        $jobStage = $jobStage->fresh();

        $this->stageLogService->record($jobStage, StageLogAction::MarkedOverdue->value, $fromStatus, $jobStage->status->value, null);

        $this->jobCardAuditService->record(
            $jobStage->jobCard,
            'STAGE_OVERDUE',
            sprintf('%s marked overdue.', $jobStage->stage->name),
            null,
            $jobStage,
        );

        event(new StageMarkedOverdue($jobStage));

        return $jobStage;
    }

    public function isOverdue(JobStage $jobStage): bool
    {
        return $jobStage->due_at !== null
            && now()->greaterThan($jobStage->due_at)
            && $jobStage->status !== StageStatus::Completed;
    }

    protected function hasApprovedDelayReport(JobStage $jobStage): bool
    {
        return $jobStage->delayReports()
            ->where('status', DelayReportStatus::Approved->value)
            ->exists();
    }

    protected function isPreviousStageCompleted(JobStage $jobStage): bool
    {
        return $jobStage->jobCard
            ->jobStages()
            ->where('sequence', '<', $jobStage->sequence)
            ->where('status', '!=', StageStatus::Completed->value)
            ->doesntExist();
    }

    protected function calculateDueAt(JobStage $jobStage): \Carbon\CarbonInterface
    {
        $durationValue = $jobStage->planned_duration_value ?? $jobStage->stage->sla_value;
        $durationUnit = $jobStage->planned_duration_unit ?? $jobStage->stage->sla_unit;

        if ($durationUnit === 'days') {
            return now()->addDays($durationValue);
        }

        return now()->addHours($durationValue);
    }
}
