<?php

namespace App\Notifications;

use App\Models\JobStage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StageOverdueNotification extends Notification
{
    use Queueable;

    public function __construct(public JobStage $jobStage) {}

    /**
     * @param  mixed  $notifiable
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * @param  mixed  $notifiable
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Stage overdue alert')
            ->line("Stage {$this->jobStage->stage->name} for job {$this->jobStage->jobCard->job_number} is overdue.")
            ->line('A delay report is required before completion.');
    }
}
