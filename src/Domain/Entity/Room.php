<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Enum\RoomStatus;
use App\Domain\Enum\RoomType;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\ValueObject\Capacity;
use App\Domain\ValueObject\GuestCount;
use App\Domain\ValueObject\HotelId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\RoomId;
use App\Domain\ValueObject\RoomNumber;

final class Room
{
    private RoomStatus $status;

    public function __construct(
        private readonly RoomId $id,
        private readonly HotelId $hotelId,
        private readonly RoomType $type,
        private readonly RoomNumber $number,
        private readonly Capacity $capacity,
        private readonly Money $pricePerNight,
    ) {
        $this->status = RoomStatus::Available;
    }

    public function getId(): RoomId
    {
        return $this->id;
    }
    public function getHotelId(): HotelId
    {
        return $this->hotelId;
    }
    public function getType(): RoomType
    {
        return $this->type;
    }
    public function getNumber(): RoomNumber
    {
        return $this->number;
    }
    public function getCapacity(): Capacity
    {
        return $this->capacity;
    }
    public function getPricePerNight(): Money
    {
        return $this->pricePerNight;
    }
    public function getStatus(): RoomStatus
    {
        return $this->status;
    }

    public function isAvailable(): bool
    {
        return $this->status === RoomStatus::Available;
    }

    public function canAccommodate(GuestCount $guests): bool
    {
        return $guests->fitsIn($this->capacity);
    }

    public function putUnderMaintenance(): void
    {
        if ($this->status === RoomStatus::Maintenance) {
            throw new BusinessRuleViolationException('Room is already under maintenance');
        }

        $this->status = RoomStatus::Maintenance;
    }

    public function makeAvailable(): void
    {
        $this->status = RoomStatus::Available;
    }
}
