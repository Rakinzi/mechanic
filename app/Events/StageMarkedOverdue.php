<?php

namespace App\Events;

use App\Models\JobStage;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StageMarkedOverdue
{
    use Dispatchable, SerializesModels;

    public function __construct(public JobStage $jobStage) {}
}
