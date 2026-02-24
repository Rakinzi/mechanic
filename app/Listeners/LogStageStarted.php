<?php

namespace App\Listeners;

use App\Events\StageStarted;

class LogStageStarted
{
    public function handle(StageStarted $event): void {}
}
