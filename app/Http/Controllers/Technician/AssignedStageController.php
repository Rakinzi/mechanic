<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\JobStage;
use Inertia\Inertia;
use Inertia\Response;

class AssignedStageController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('technician/AssignedStages', [
            'stages' => JobStage::query()
                ->with(['jobCard.vehicle.client', 'stage', 'delayReports'])
                ->assignedToTechnician(request()->user())
                ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')
                ->orderBy('due_at')
                ->get(),
        ]);
    }

    public function show(JobStage $jobStage): Response
    {
        $this->authorize('view', $jobStage);

        return Inertia::render('technician/StageExecution', [
            'jobStage' => $jobStage->load([
                'jobCard.vehicle.client',
                'stage',
                'logs.actor',
                'delayReports.submitter',
                'delayReports.reviewer',
                'media',
            ]),
        ]);
    }
}
