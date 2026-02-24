<?php

namespace App\Providers;

use App\Events\DelayReportReviewed;
use App\Events\DelayReportSubmitted;
use App\Events\StageCompleted;
use App\Events\StageMarkedOverdue;
use App\Events\StageStarted;
use App\Listeners\LogDelayReportReviewed;
use App\Listeners\LogDelayReportSubmitted;
use App\Listeners\LogStageCompleted;
use App\Listeners\LogStageMarkedOverdue;
use App\Listeners\LogStageStarted;
use App\Models\DelayReport;
use App\Models\JobCard;
use App\Models\JobStage;
use App\Models\User;
use App\Policies\DelayReportPolicy;
use App\Policies\JobCardPolicy;
use App\Policies\JobStagePolicy;
use App\Policies\UserPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerPolicies();
        $this->registerEventListeners();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );

        app()[\Spatie\Permission\PermissionRegistrar::class]->setPermissionClass(Permission::class);
        app()[\Spatie\Permission\PermissionRegistrar::class]->setRoleClass(Role::class);
    }

    protected function registerPolicies(): void
    {
        Gate::policy(JobCard::class, JobCardPolicy::class);
        Gate::policy(JobStage::class, JobStagePolicy::class);
        Gate::policy(DelayReport::class, DelayReportPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
    }

    protected function registerEventListeners(): void
    {
        Event::listen(StageStarted::class, LogStageStarted::class);
        Event::listen(StageMarkedOverdue::class, LogStageMarkedOverdue::class);
        Event::listen(DelayReportSubmitted::class, LogDelayReportSubmitted::class);
        Event::listen(DelayReportReviewed::class, LogDelayReportReviewed::class);
        Event::listen(StageCompleted::class, LogStageCompleted::class);
    }
}
