<?php

namespace App\Http\Controllers;

use App\Models\JobCard;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class JobCardSummaryController extends Controller
{
    public function admin(JobCard $jobCard, Request $request): Response
    {
        $this->authorize('downloadSummary', $jobCard);

        return $this->renderSummaryView($jobCard, $request);
    }

    public function client(JobCard $jobCard, Request $request): Response
    {
        $this->authorize('downloadSummary', $jobCard);

        return $this->renderSummaryView($jobCard, $request);
    }

    protected function renderSummaryView(JobCard $jobCard, Request $request): Response
    {
        $jobCard->load([
            'vehicle.client',
            'creator',
            'jobStages.stage',
            'jobStages.assignedMechanic',
            'jobStages.logs.actor',
            'jobStages.delayReports.submitter',
            'jobStages.delayReports.reviewer',
            'jobStages.media',
        ]);

        $inline = ! $request->boolean('download');
        $disposition = $inline ? 'inline' : 'attachment';
        $filename = sprintf('job-card-%s-summary.html', $jobCard->job_number);

        return response()
            ->view('job-cards.summary', ['jobCard' => $jobCard])
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Content-Disposition', "{$disposition}; filename=\"{$filename}\"");
    }
}
