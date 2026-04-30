<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum RoomStatus: string
{
    case Available   = 'available';
    case Maintenance = 'maintenance';
    case Inactive    = 'inactive';
}
