<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\IntakeJobCardRequest;
use App\Http\Requests\UpdateJobStagePlanRequest;
use App\Models\Client;
use App\Models\JobCard;
use App\Models\JobStage;
use App\Models\Stage;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\JobCardAuditService;
use App\Services\JobCardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

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
            ->with(['vehicle.client', 'jobStages.stage', 'jobStages.assignedTechnician', 'jobStages.technicians'])
            ->withCount(['jobStages as pending_delay_reports_count' => fn ($query) => $query->join('delay_reports', 'delay_reports.job_stage_id', '=', 'job_stages.id')->where('delay_reports.status', 'PENDING')])
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
                    'uuid' => $jobCard->uuid,
                    'job_number' => $jobCard->job_number,
                    'status' => $jobCard->status,
                    'current_stage' => ($activeStage = $jobCard->jobStages->firstWhere('status', '!=', 'COMPLETED'))
                        ?->stage
                        ?->name,
                    'current_stage_status' => $activeStage?->status,
                    'current_technicians' => $activeStage
                        ? ($activeStage->technicians->isNotEmpty()
                            ? $activeStage->technicians->pluck('name')->join(', ')
                            : $activeStage->assignedTechnician?->name)
                        : null,
                    'vehicle' => $jobCard->vehicle->make.' '.$jobCard->vehicle->model,
                    'registration_number' => $jobCard->vehicle->registration_number,
                    'client_name' => $jobCard->vehicle->client->name,
                    'created_at' => $jobCard->created_at?->toDateTimeString(),
                    'pending_delay_reports_count' => $jobCard->pending_delay_reports_count,
                ]),
            'clients' => Client::query()->orderBy('name')->get(['id', 'name', 'email']),
            'vehicles' => Vehicle::query()->orderBy('registration_number')->get(['id', 'client_id', 'registration_number', 'make', 'model']),
            'stages' => Stage::query()->orderBy('sequence')->get(['id', 'name', 'sequence', 'sla_value', 'sla_unit']),
            'technicians' => User::query()->role('technician')->orderBy('name')->get(['id', 'name', 'email']),
            'admins' => User::query()->role('admin')->orderBy('name')->get(['id', 'name', 'email']),
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
                'currentJobStage.stage',
                'audits.actor',
                'audits.jobStage.stage',
                'jobStages.stage',
                'jobStages.assignedTechnician',
                'jobStages.technicians',
                'jobStages.logs.actor',
                'jobStages.delayReports.submitter',
                'jobStages.delayReports.reviewer',
                'jobStages.delayReports.media',
            ]),
            'technicians' => User::query()->role('technician')->orderBy('name')->get(['id', 'name', 'email']),
            'admins' => User::query()->role('admin')->orderBy('name')->get(['id', 'name', 'email']),
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

    public function updateStagePlan(UpdateJobStagePlanRequest $request, JobCard $jobCard, JobStage $jobStage): RedirectResponse
    {
        $this->authorize('update', $jobCard);
        $this->authorize('updatePlan', $jobStage);

        if ($jobStage->job_card_id !== $jobCard->id) {
            abort(404);
        }

        try {
            $this->jobCardService->updateFutureStage($jobStage->load(['jobCard', 'stage']), $request->validated(), $request->user());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['job_stage' => $exception->getMessage()]);
        }

        return back()->with('success', 'Future stage updated successfully.');
    }

    public function close(JobCard $jobCard): RedirectResponse
    {
        $this->authorize('close', $jobCard);

        $jobCard->update([
            'status' => 'COMPLETED',
            'current_job_stage_id' => null,
            'closed_at' => now(),
        ]);

        app(JobCardAuditService::class)->record(
            $jobCard,
            'JOB_CARD_CLOSED',
            'Job card manually closed by admin.',
            request()->user(),
        );

        return back()->with('success', 'Job card closed successfully.');
    }
}
