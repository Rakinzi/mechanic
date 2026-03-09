<?php

namespace App\Services;

use App\Enums\DelayReportStatus;
use App\Enums\StageLogAction;
use App\Enums\StageStatus;
use App\Events\DelayReportReviewed;
use App\Events\DelayReportSubmitted;
use App\Models\DelayReport;
use App\Models\JobStage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DelayReportService
{
    public function __construct(
        protected StageLogService $stageLogService,
        protected JobCardAuditService $jobCardAuditService,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function submit(JobStage $jobStage, User $actor, array $payload): DelayReport
    {
        $fromStatus = $jobStage->status->value;

        /** @var DelayReport $delayReport */
        $delayReport = DB::transaction(function () use ($jobStage, $actor, $payload): DelayReport {
            $delayReport = $jobStage->delayReports()->create([
                'submitted_by' => $actor->id,
                'reason_category' => $payload['reason_category'],
                'explanation' => $payload['explanation'],
                'proposed_eta' => $payload['proposed_eta'],
                'status' => DelayReportStatus::Pending,
            ]);

            $jobStage->update([
                'status' => $this->shouldMarkOverdue($jobStage) ? StageStatus::Overdue : StageStatus::Blocked,
                'blocked_at' => now(),
                'latest_note' => $payload['explanation'],
            ]);

            return $delayReport;
        });

        $jobStage = $jobStage->fresh();

        $this->stageLogService->record(
            $jobStage,
            StageLogAction::DelaySubmitted->value,
            $fromStatus,
            $jobStage->status->value,
            $actor,
            [
                'delay_report_id' => $delayReport->id,
                'reason_category' => $delayReport->reason_category->value,
            ],
        );

        $this->jobCardAuditService->record(
            $jobStage->jobCard,
            'DELAY_REPORT_SUBMITTED',
            sprintf('Delay report submitted for %s.', $jobStage->stage->name),
            $actor,
            $jobStage,
            [
                'delay_report_id' => $delayReport->id,
                'reason_category' => $delayReport->reason_category->value,
                'proposed_eta' => $delayReport->proposed_eta?->toDateTimeString(),
            ],
        );

        DB::afterCommit(fn (): mixed => event(new DelayReportSubmitted($delayReport, $actor)));

        return $delayReport->fresh(['jobStage', 'submitter']);
    }

    public function approve(DelayReport $delayReport, User $reviewer, ?string $comment = null): DelayReport
    {
        $delayReport->forceFill([
            'status' => DelayReportStatus::Approved,
            'reviewed_by' => $reviewer->id,
            'review_comment' => $comment,
            'reviewed_at' => now(),
        ])->save();

        $jobStage = $delayReport->jobStage;

        if ($jobStage->due_at !== null && $delayReport->proposed_eta->greaterThan($jobStage->due_at)) {
            $jobStage->update([
                'due_at' => $delayReport->proposed_eta,
                'planned_due_at' => $delayReport->proposed_eta,
            ]);
        }

        $this->stageLogService->record(
            $jobStage,
            StageLogAction::DelayReviewed->value,
            $jobStage->status->value,
            $jobStage->status->value,
            $reviewer,
            [
                'delay_report_id' => $delayReport->id,
                'decision' => DelayReportStatus::Approved->value,
                'comment' => $comment,
            ],
        );

        $this->jobCardAuditService->record(
            $jobStage->jobCard,
            'DELAY_REPORT_APPROVED',
            sprintf('Delay report approved for %s.', $jobStage->stage->name),
            $reviewer,
            $jobStage,
            [
                'delay_report_id' => $delayReport->id,
                'comment' => $comment,
            ],
        );

        DB::afterCommit(fn (): mixed => event(new DelayReportReviewed($delayReport->fresh(), $reviewer)));

        return $delayReport->fresh(['jobStage', 'reviewer']);
    }

    public function reject(DelayReport $delayReport, User $reviewer, ?string $comment = null): DelayReport
    {
        $delayReport->forceFill([
            'status' => DelayReportStatus::Rejected,
            'reviewed_by' => $reviewer->id,
            'review_comment' => $comment,
            'reviewed_at' => now(),
        ])->save();

        $this->stageLogService->record(
            $delayReport->jobStage,
            StageLogAction::DelayReviewed->value,
            $delayReport->jobStage->status->value,
            $delayReport->jobStage->status->value,
            $reviewer,
            [
                'delay_report_id' => $delayReport->id,
                'decision' => DelayReportStatus::Rejected->value,
                'comment' => $comment,
            ],
        );

        $this->jobCardAuditService->record(
            $delayReport->jobStage->jobCard,
            'DELAY_REPORT_REJECTED',
            sprintf('Delay report rejected for %s.', $delayReport->jobStage->stage->name),
            $reviewer,
            $delayReport->jobStage,
            [
                'delay_report_id' => $delayReport->id,
                'comment' => $comment,
            ],
        );

        DB::afterCommit(fn (): mixed => event(new DelayReportReviewed($delayReport->fresh(), $reviewer)));

        return $delayReport->fresh(['jobStage', 'reviewer']);
    }

    protected function shouldMarkOverdue(JobStage $jobStage): bool
    {
        return $jobStage->due_at !== null && now()->greaterThan($jobStage->due_at);
    }
}
