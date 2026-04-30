<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum RoomType: string
{
    case Single = 'single';
    case Double = 'double';
    case Suite  = 'suite';
    case Deluxe = 'deluxe';
}
