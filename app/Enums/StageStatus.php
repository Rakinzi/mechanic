<?php

namespace App\Enums;

enum StageStatus: string
{
    case NotStarted = 'NOT_STARTED';
    case InProgress = 'IN_PROGRESS';
    case Blocked = 'BLOCKED';
    case Overdue = 'OVERDUE';
    case Completed = 'COMPLETED';
}
