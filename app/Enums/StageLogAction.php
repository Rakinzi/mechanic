<?php

namespace App\Enums;

enum StageLogAction: string
{
    case Started = 'STARTED';
    case Paused = 'PAUSED';
    case Blocked = 'BLOCKED';
    case MarkedOverdue = 'MARKED_OVERDUE';
    case Completed = 'COMPLETED';
    case DelaySubmitted = 'DELAY_SUBMITTED';
    case DelayReviewed = 'DELAY_REVIEWED';
}
