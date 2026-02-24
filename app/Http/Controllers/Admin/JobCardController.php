<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\IntakeJobCardRequest;
use App\Models\Client;
use App\Models\JobCard;
use App\Models\Stage;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\JobCardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JobCardController extends Controller
{
    public function __construct(protected JobCardService $jobCardService) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', JobCard::class);

        $status = $request->string('status')->value();
        $clientId = $request->integer('client_id') ?: null;
        $vehicleRegistration = $request->string('vehicle_registration')->value();
        $overdueOnly = $request->boolean('overdue_only');

        $jobCards = JobCard::query()
            ->with(['vehicle.client', 'jobStages.stage'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($clientId, fn ($query) => $query->whereHas('vehicle.client', fn ($clientQuery) => $clientQuery->whereKey($clientId)))
            ->when($vehicleRegistration, fn ($query) => $query->whereHas('vehicle', fn ($vehicleQuery) => $vehicleQuery->where('registration_number', 'like', '%'.$vehicleRegistration.'%')))
            ->when($overdueOnly, fn ($query) => $query->whereHas('jobStages', fn ($stageQuery) => $stageQuery->where('status', 'OVERDUE')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/JobCardsIndex', [
            'jobCards' => $jobCards
                ->through(fn (JobCard $jobCard): array => [
                    'id' => $jobCard->id,
                    'job_number' => $jobCard->job_number,
                    'status' => $jobCard->status,
                    'vehicle' => $jobCard->vehicle->make.' '.$jobCard->vehicle->model,
                    'registration_number' => $jobCard->vehicle->registration_number,
                    'client_name' => $jobCard->vehicle->client->name,
                    'created_at' => $jobCard->created_at?->toDateTimeString(),
                ]),
            'clients' => Client::query()->orderBy('name')->get(['id', 'name', 'email']),
            'vehicles' => Vehicle::query()->orderBy('registration_number')->get(['id', 'client_id', 'registration_number', 'make', 'model']),
            'stages' => Stage::query()->orderBy('sequence')->get(['id', 'name', 'sequence']),
            'mechanics' => User::query()->role('mechanic')->orderBy('name')->get(['id', 'name', 'email']),
            'filters' => [
                'status' => $status,
                'client_id' => $clientId,
                'vehicle_registration' => $vehicleRegistration,
                'overdue_only' => $overdueOnly,
            ],
        ]);
    }

    public function show(JobCard $jobCard): Response
    {
        $this->authorize('view', $jobCard);

        return Inertia::render('admin/JobCardDetail', [
            'jobCard' => $jobCard->load([
                'vehicle.client',
                'creator',
                'jobStages.stage',
                'jobStages.assignedMechanic',
                'jobStages.logs.actor',
                'jobStages.delayReports.submitter',
                'jobStages.delayReports.reviewer',
            ]),
            'summaryUrl' => route('admin.job-cards.summary', $jobCard),
        ]);
    }

    public function store(IntakeJobCardRequest $request): RedirectResponse
    {
        $this->authorize('create', JobCard::class);

        $client = $request->filled('client_id')
            ? Client::query()->findOrFail($request->integer('client_id'))
            : Client::query()->create($request->input('client'));

        $vehicle = $request->filled('vehicle_id')
            ? Vehicle::query()->findOrFail($request->integer('vehicle_id'))
            : $client->vehicles()->create($request->input('vehicle'));

        $jobCard = $this->jobCardService->create($vehicle, $request->user(), $request->validated());

        return redirect()
            ->route('admin.job-cards.show', $jobCard)
            ->with('success', 'Job card created successfully.');
    }

    public function close(JobCard $jobCard): RedirectResponse
    {
        $this->authorize('close', $jobCard);

        $jobCard->update([
            'status' => 'COMPLETED',
            'closed_at' => now(),
        ]);

        return back()->with('success', 'Job card closed successfully.');
    }
}
