<?php

namespace App\Events;

use App\Models\DelayReport;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DelayReportReviewed
{
    use Dispatchable, SerializesModels;

    public function __construct(public DelayReport $delayReport, public User $actor) {}
}
