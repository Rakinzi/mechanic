<?php

namespace App\Listeners;

use App\Events\DelayReportSubmitted;
use App\Models\User;
use App\Notifications\DelayReportSubmittedNotification;
use Illuminate\Support\Facades\Notification;

class LogDelayReportSubmitted
{
    public function handle(DelayReportSubmitted $event): void
    {
        $admins = User::query()->role('admin')->get();

        Notification::send($admins, new DelayReportSubmittedNotification($event->delayReport));
    }
}
