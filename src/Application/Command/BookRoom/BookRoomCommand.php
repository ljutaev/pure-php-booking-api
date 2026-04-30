<?php

declare(strict_types=1);

namespace App\Application\Command\BookRoom;

use App\Application\Bus\CommandInterface;

final class BookRoomCommand implements CommandInterface
{
    public function __construct(
        public readonly string $userId,
        public readonly string $roomId,
        public readonly string $checkIn,
        public readonly string $checkOut,
        public readonly int $guests,
        public readonly ?string $specialRequests = null,
    ) {
    }
}
