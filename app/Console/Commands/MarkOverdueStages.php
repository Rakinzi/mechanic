<?php

namespace App\Console\Commands;

use App\Enums\StageStatus;
use App\Models\JobStage;
use App\Services\JobStageService;
use Illuminate\Console\Command;

class MarkOverdueStages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stages:mark-overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark in-progress stages as overdue when due_at has passed';

    public function __construct(protected JobStageService $jobStageService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $stages = JobStage::query()
            ->with(['stage', 'jobCard.vehicle.client.user', 'assignedMechanic'])
            ->whereIn('status', [StageStatus::InProgress->value, StageStatus::Blocked->value])
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->get();

        foreach ($stages as $stage) {
            $this->jobStageService->markOverdue($stage);
        }

        $this->info("Processed {$stages->count()} overdue stage(s).");

        return self::SUCCESS;
    }
}
