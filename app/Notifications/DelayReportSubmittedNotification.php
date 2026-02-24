<?php

namespace App\Notifications;

use App\Models\DelayReport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DelayReportSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(public DelayReport $delayReport) {}

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
            ->subject('Delay report submitted')
            ->line("A delay report was submitted for stage {$this->delayReport->jobStage->stage->name}.")
            ->line("Reason: {$this->delayReport->reason_category->value}")
            ->line('Please review and approve or reject it.');
    }
}
