<?php

namespace App\Listeners;

use App\Events\DelayReportReviewed;
use App\Notifications\DelayReportReviewedNotification;

class LogDelayReportReviewed
{
    public function handle(DelayReportReviewed $event): void
    {
        $event->delayReport->submitter->notify(new DelayReportReviewedNotification($event->delayReport));
    }
}
