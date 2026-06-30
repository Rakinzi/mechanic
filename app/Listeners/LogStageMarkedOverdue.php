<?php

namespace App\Listeners;

use App\Events\StageMarkedOverdue;
use App\Models\User;
use App\Notifications\StageOverdueNotification;
use Illuminate\Support\Facades\Notification;

class LogStageMarkedOverdue
{
    public function handle(StageMarkedOverdue $event): void
    {
        $admins = User::query()->role('admin')->get();

        Notification::send($admins, new StageOverdueNotification($event->jobStage));

        if ($event->jobStage->assignedTechnician !== null) {
            $event->jobStage->assignedTechnician->notify(new StageOverdueNotification($event->jobStage));
        }
    }
}
