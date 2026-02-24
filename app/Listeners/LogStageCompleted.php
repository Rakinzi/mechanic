<?php

namespace App\Listeners;

use App\Events\StageCompleted;
use App\Notifications\JobCardCompletedNotification;

class LogStageCompleted
{
    public function handle(StageCompleted $event): void
    {
        $jobCard = $event->jobStage->jobCard;

        if ($jobCard->status !== 'COMPLETED') {
            return;
        }

        $clientUser = $jobCard->vehicle->client->user;

        if ($clientUser !== null) {
            $clientUser->notify(new JobCardCompletedNotification($jobCard));
        }
    }
}
