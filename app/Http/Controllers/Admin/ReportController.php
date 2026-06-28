<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DelayReportStatus;
use App\Enums\StageStatus;
use App\Http\Controllers\Controller;
use App\Models\DelayReport;
use App\Models\JobCard;
use App\Models\JobStage;
use App\Models\Stage;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(): Response
    {
        $stageTurnaround = Stage::query()
            ->with(['jobStages' => fn ($query) => $query
                ->whereNotNull('started_at')
                ->whereNotNull('completed_at'),
            ])
            ->orderBy('sequence')
            ->get()
            ->map(fn (Stage $stage): array => [
                'stage' => $stage->name,
                'avg_hours' => round(
                    $stage->jobStages->avg(
                        fn (JobStage $jobStage): float => (float) $jobStage->completed_at->diffInMinutes($jobStage->started_at) / 60
                    ) ?? 0,
                    2
                ),
            ]);

        $delaysByMechanic = User::query()
            ->role('mechanic')
            ->withCount('submittedDelayReports')
            ->orderByDesc('submitted_delay_reports_count')
            ->get(['id', 'name'])
            ->map(fn (User $technician): array => [
                'technician' => $technician->name,
                'delay_count' => $technician->submitted_delay_reports_count,
            ]);

        $commonDelayReasons = DelayReport::query()
            ->selectRaw('reason_category, count(*) as total')
            ->groupBy('reason_category')
            ->orderByDesc('total')
            ->get()
            ->map(fn (DelayReport $delayReport): array => [
                'reason_category' => $delayReport->reason_category->value,
                'total' => (int) $delayReport->total,
            ]);

        return Inertia::render('admin/Reports', [
            'summary' => [
                'totalJobCards' => JobCard::query()->count(),
                'completedJobCards' => JobCard::query()->where('status', 'COMPLETED')->count(),
                'overdueStages' => JobStage::query()->where('status', StageStatus::Overdue->value)->count(),
                'pendingDelayReports' => DelayReport::query()->where('status', DelayReportStatus::Pending->value)->count(),
                'approvedDelayReports' => DelayReport::query()->where('status', DelayReportStatus::Approved->value)->count(),
            ],
            'analytics' => [
                'stage_turnaround' => $stageTurnaround,
                'delays_by_technician' => $delaysByMechanic,
                'common_delay_reasons' => $commonDelayReasons,
            ],
        ]);
    }
}
