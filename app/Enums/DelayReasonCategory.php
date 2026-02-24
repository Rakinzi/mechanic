<?php

namespace App\Enums;

enum DelayReasonCategory: string
{
    case PartsDelay = 'PARTS_DELAY';
    case VendorDelay = 'EXTERNAL_VENDOR';
    case Rework = 'REWORK';
    case ClientApproval = 'CLIENT_APPROVAL';
    case AdditionalDamage = 'ADDITIONAL_DAMAGE';
    case Other = 'OTHER';
}
