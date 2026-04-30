<?php

declare(strict_types=1);

namespace App\Application\Command\BookRoom;

use App\Application\Bus\CommandHandlerInterface;
use App\Application\Bus\CommandInterface;
use App\Domain\Entity\Booking;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Exception\RoomNotAvailableException;
use App\Domain\Repository\BookingRepositoryInterface;
use App\Domain\Repository\RoomRepositoryInterface;
use App\Domain\ValueObject\BookingId;
use App\Domain\ValueObject\DateRange;
use App\Domain\ValueObject\GuestCount;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\RoomId;
use App\Domain\ValueObject\UserId;

final class BookRoomHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly RoomRepositoryInterface $rooms,
        private readonly BookingRepositoryInterface $bookings,
    ) {
    }

    public function handle(CommandInterface $command): BookingId
    {
        assert($command instanceof BookRoomCommand);

        $room      = $this->rooms->findById(new RoomId($command->roomId));
        $dateRange = new DateRange(
            new \DateTimeImmutable($command->checkIn),
            new \DateTimeImmutable($command->checkOut),
        );
        $guests = new GuestCount($command->guests);

        if (!$room->isAvailable()) {
            throw new RoomNotAvailableException('Room is not available for booking');
        }

        if (!$room->canAccommodate($guests)) {
            throw new BusinessRuleViolationException(
                "Room capacity ({$room->getCapacity()->value}) exceeded by guest count ({$guests->value})"
            );
        }

        if ($this->bookings->findByRoomAndDateRange($room->getId(), $dateRange) !== []) {
            throw new RoomNotAvailableException('Room is already booked for the selected dates');
        }

        $totalPrice = new Money(
            $room->getPricePerNight()->amount * $dateRange->nights(),
            $room->getPricePerNight()->currency,
        );

        $booking = new Booking(
            BookingId::generate(),
            new UserId($command->userId),
            $room->getId(),
            $room->getHotelId(),
            $dateRange,
            $guests,
            $totalPrice,
            $command->specialRequests,
        );

        $this->bookings->save($booking);

        return $booking->getId();
    }
}
