<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Enum\HotelStatus;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\ValueObject\Address;
use App\Domain\ValueObject\GeoPoint;
use App\Domain\ValueObject\HotelId;
use App\Domain\ValueObject\HotelName;
use App\Domain\ValueObject\StarRating;
use App\Domain\ValueObject\UserId;

final class Hotel
{
    private HotelStatus $status;
    private \DateTimeImmutable $createdAt;

    public function __construct(
        private readonly HotelId $id,
        private readonly HotelName $name,
        private readonly string $description,
        private readonly Address $address,
        private readonly GeoPoint $location,
        private readonly StarRating $starRating,
        private readonly UserId $managerId,
    ) {
        $this->status    = HotelStatus::Active;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): HotelId
    {
        return $this->id;
    }
    public function getName(): HotelName
    {
        return $this->name;
    }
    public function getDescription(): string
    {
        return $this->description;
    }
    public function getAddress(): Address
    {
        return $this->address;
    }
    public function getLocation(): GeoPoint
    {
        return $this->location;
    }
    public function getStarRating(): StarRating
    {
        return $this->starRating;
    }
    public function getStatus(): HotelStatus
    {
        return $this->status;
    }
    public function getManagerId(): UserId
    {
        return $this->managerId;
    }
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function activate(): void
    {
        if ($this->status === HotelStatus::Active) {
            throw new BusinessRuleViolationException('Hotel is already active');
        }

        $this->status = HotelStatus::Active;
    }

    public function deactivate(): void
    {
        if ($this->status === HotelStatus::Inactive) {
            throw new BusinessRuleViolationException('Hotel is already inactive');
        }

        $this->status = HotelStatus::Inactive;
    }

    public function suspend(): void
    {
        $this->status = HotelStatus::Suspended;
    }

    public static function reconstitute(
        HotelId $id,
        HotelName $name,
        string $description,
        Address $address,
        GeoPoint $location,
        StarRating $starRating,
        UserId $managerId,
        HotelStatus $status,
        \DateTimeImmutable $createdAt,
    ): self {
        $hotel            = new self($id, $name, $description, $address, $location, $starRating, $managerId);
        $hotel->status    = $status;
        $hotel->createdAt = $createdAt;

        return $hotel;
    }
}
