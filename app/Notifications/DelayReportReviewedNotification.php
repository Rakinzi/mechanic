<?php

namespace App\Notifications;

use App\Models\DelayReport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DelayReportReviewedNotification extends Notification
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
            ->subject('Delay report reviewed')
            ->line("Your delay report is {$this->delayReport->status->value}.")
            ->line($this->delayReport->review_comment ?? 'No review comment provided.');
    }
}
