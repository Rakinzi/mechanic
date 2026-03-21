<?php

namespace App\Http\Controllers;

use App\Models\JobCard;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JobCardSummaryController extends Controller
{
    public function admin(JobCard $jobCard, Request $request): Response|StreamedResponse
    {
        $this->authorize('downloadSummary', $jobCard);

        return $this->renderSummaryView($jobCard, $request);
    }

    public function client(JobCard $jobCard, Request $request): Response|StreamedResponse
    {
        $this->authorize('downloadSummary', $jobCard);

        return $this->renderSummaryView($jobCard, $request);
    }

    protected function renderSummaryView(JobCard $jobCard, Request $request): Response|StreamedResponse
    {
        $jobCard->load([
            'vehicle.client',
            'creator',
            'jobStages.stage',
            'jobStages.assignedMechanic',
            'jobStages.mechanics',
            'jobStages.logs.actor',
            'jobStages.delayReports.submitter',
            'jobStages.delayReports.reviewer',
            'jobStages.delayReports.media',
            'jobStages.media',
        ]);

        if ($request->boolean('download')) {
            $filename = sprintf('job-card-%s-summary.pdf', $jobCard->job_number);

            return Pdf::loadView('job-cards.summary', ['jobCard' => $jobCard])
                ->download($filename);
        }

        return response()
            ->view('job-cards.summary', ['jobCard' => $jobCard])
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Content-Disposition', 'inline');
    }
}
