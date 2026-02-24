<?php

namespace App\Notifications;

use App\Models\JobCard;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JobCardCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(public JobCard $jobCard) {}

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
            ->subject('Vehicle repair completed')
            ->line("Repair {$this->jobCard->job_number} has been completed.")
            ->line('You can now review the full repair timeline in the client portal.');
    }
}
