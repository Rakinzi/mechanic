<?php

namespace App\Events;

use App\Models\JobStage;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StageStarted
{
    use Dispatchable, SerializesModels;

    public function __construct(public JobStage $jobStage, public User $actor) {}
}
