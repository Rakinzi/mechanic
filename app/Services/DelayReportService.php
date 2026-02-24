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
    public function __construct(protected StageLogService $stageLogService) {}

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
                'status' => StageStatus::Overdue,
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

        event(new DelayReportSubmitted($delayReport, $actor));

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

        event(new DelayReportReviewed($delayReport->fresh(), $reviewer));

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

        event(new DelayReportReviewed($delayReport->fresh(), $reviewer));

        return $delayReport->fresh(['jobStage', 'reviewer']);
    }
}
