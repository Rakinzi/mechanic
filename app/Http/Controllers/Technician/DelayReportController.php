<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitDelayReportRequest;
use App\Models\JobStage;
use App\Services\DelayReportService;
use Illuminate\Http\RedirectResponse;

class DelayReportController extends Controller
{
    public function __construct(protected DelayReportService $delayReportService) {}

    public function store(SubmitDelayReportRequest $request, JobStage $jobStage): RedirectResponse
    {
        $this->authorize('submitDelay', $jobStage);

        $delayReport = $this->delayReportService->submit($jobStage, $request->user(), $request->validated());

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $attachment) {
                $delayReport->addMedia($attachment)->toMediaCollection('attachments');
            }
        }

        return back()->with('success', 'Delay report submitted successfully.');
    }
}
