<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\JobCard;
use Inertia\Inertia;
use Inertia\Response;

class RepairController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('client/MyRepairs', [
            'jobCards' => JobCard::query()
                ->whereHas('vehicle.client', fn ($query) => $query->where('user_id', request()->user()->id))
                ->with(['vehicle', 'jobStages.stage'])
                ->latest()
                ->get(),
        ]);
    }

    public function show(JobCard $jobCard): Response
    {
        $this->authorize('view', $jobCard);

        return Inertia::render('client/RepairProgress', [
            'jobCard' => $jobCard->load([
                'vehicle',
                'jobStages.stage',
                'jobStages.logs.actor',
                'jobStages.media',
            ]),
            'summaryUrl' => route('client.repairs.summary', $jobCard),
        ]);
    }
}
