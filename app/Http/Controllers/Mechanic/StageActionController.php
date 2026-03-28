<?php

namespace App\Http\Controllers\Mechanic;

use App\Http\Controllers\Controller;
use App\Http\Requests\StageActionRequest;
use App\Models\JobStage;
use App\Services\JobStageService;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class StageActionController extends Controller
{
    public function __construct(protected JobStageService $jobStageService) {}

    public function start(StageActionRequest $request, JobStage $jobStage): RedirectResponse
    {
        $this->authorize('start', $jobStage);

        try {
            $this->jobStageService->start($jobStage->load(['stage', 'jobCard']), $request->user(), $request->string('note')->toString());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['stage' => $exception->getMessage()]);
        }

        return back()->with('success', 'Stage started successfully.');
    }

    public function pause(StageActionRequest $request, JobStage $jobStage): RedirectResponse
    {
        $this->authorize('pause', $jobStage);

        $this->jobStageService->pause($jobStage, $request->user(), $request->string('note')->toString());

        return back()->with('success', 'Stage paused successfully.');
    }

    public function block(StageActionRequest $request, JobStage $jobStage): RedirectResponse
    {
        $this->authorize('block', $jobStage);

        $this->jobStageService->block($jobStage, $request->user(), $request->string('note')->toString());

        return back()->with('success', 'Stage blocked successfully.');
    }

    public function complete(StageActionRequest $request, JobStage $jobStage): RedirectResponse
    {
        $this->authorize('complete', $jobStage);

        try {
            $this->jobStageService->complete($jobStage->load('jobCard'), $request->user(), $request->string('note')->toString());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['stage' => $exception->getMessage()]);
        }

        return back()->with('success', 'Stage completed successfully.');
    }
}
