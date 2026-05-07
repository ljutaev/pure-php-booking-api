<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum UserRole: string
{
    case Guest    = 'guest';
    case Customer = 'customer';
    case Manager  = 'manager';
    case Admin    = 'admin';
}
