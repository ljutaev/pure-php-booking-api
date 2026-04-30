<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum HotelStatus: string
{
    case Active    = 'active';
    case Inactive  = 'inactive';
    case Suspended = 'suspended';
}
