<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewDelayReportRequest;
use App\Models\DelayReport;
use App\Services\DelayReportService;
use Illuminate\Http\RedirectResponse;

class DelayReportReviewController extends Controller
{
    public function __construct(protected DelayReportService $delayReportService) {}

    public function approve(ReviewDelayReportRequest $request, DelayReport $delayReport): RedirectResponse
    {
        $this->authorize('approve', $delayReport);

        $this->delayReportService->approve($delayReport, $request->user(), $request->string('comment')->toString());

        return back()->with('success', 'Delay report approved.');
    }

    public function reject(ReviewDelayReportRequest $request, DelayReport $delayReport): RedirectResponse
    {
        $this->authorize('reject', $delayReport);

        $this->delayReportService->reject($delayReport, $request->user(), $request->string('comment')->toString());

        return back()->with('success', 'Delay report rejected.');
    }
}
