<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DelayReportStatus;
use App\Enums\StageStatus;
use App\Http\Controllers\Controller;
use App\Models\DelayReport;
use App\Models\JobCard;
use App\Models\JobStage;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('admin/Dashboard', [
            'kpis' => [
                'openJobCards' => JobCard::query()->where('status', 'OPEN')->count(),
                'overdueStages' => JobStage::query()->where('status', StageStatus::Overdue->value)->count(),
                'pendingDelayReports' => DelayReport::query()->where('status', DelayReportStatus::Pending->value)->count(),
                'completedJobCards' => JobCard::query()->where('status', 'COMPLETED')->count(),
                'nearDeadlineJobs' => JobCard::query()
                    ->whereIn('status', ['OPEN', 'IN_PROGRESS'])
                    ->whereNotNull('promised_delivery_at')
                    ->whereBetween('promised_delivery_at', [now(), now()->addDay()])
                    ->count(),
            ],
            'overdueStages' => JobStage::query()
                ->with(['jobCard.vehicle.client', 'stage', 'assignedTechnician'])
                ->where('status', StageStatus::Overdue->value)
                ->orderBy('due_at')
                ->get(),
            'nearDeadlineJobs' => JobCard::query()
                ->with(['vehicle.client'])
                ->whereIn('status', ['OPEN', 'IN_PROGRESS'])
                ->whereNotNull('promised_delivery_at')
                ->whereBetween('promised_delivery_at', [now(), now()->addDay()])
                ->orderBy('promised_delivery_at')
                ->get(),
        ]);
    }
}
