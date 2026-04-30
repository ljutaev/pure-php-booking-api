<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Enum\BookingStatus;
use App\Domain\Exception\BookingCannotBeCancelledException;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\ValueObject\BookingId;
use App\Domain\ValueObject\DateRange;
use App\Domain\ValueObject\GuestCount;
use App\Domain\ValueObject\HotelId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\PaymentId;
use App\Domain\ValueObject\RoomId;
use App\Domain\ValueObject\UserId;

final class Booking
{
    private BookingStatus $status;
    private ?PaymentId $paymentId;
    private \DateTimeImmutable $createdAt;

    public function __construct(
        private readonly BookingId $id,
        private readonly UserId $userId,
        private readonly RoomId $roomId,
        private readonly HotelId $hotelId,
        private readonly DateRange $dateRange,
        private readonly GuestCount $guests,
        private readonly Money $totalPrice,
        private readonly ?string $specialRequests,
    ) {
        $this->status    = BookingStatus::Pending;
        $this->paymentId = null;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): BookingId
    {
        return $this->id;
    }
    public function getUserId(): UserId
    {
        return $this->userId;
    }
    public function getRoomId(): RoomId
    {
        return $this->roomId;
    }
    public function getHotelId(): HotelId
    {
        return $this->hotelId;
    }
    public function getDateRange(): DateRange
    {
        return $this->dateRange;
    }
    public function getGuests(): GuestCount
    {
        return $this->guests;
    }
    public function getTotalPrice(): Money
    {
        return $this->totalPrice;
    }
    public function getStatus(): BookingStatus
    {
        return $this->status;
    }
    public function getPaymentId(): ?PaymentId
    {
        return $this->paymentId;
    }
    public function getSpecialRequests(): ?string
    {
        return $this->specialRequests;
    }
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function confirm(): void
    {
        if ($this->status !== BookingStatus::Pending) {
            throw new BusinessRuleViolationException(
                "Cannot confirm booking with status: {$this->status->value}"
            );
        }

        $this->status = BookingStatus::Confirmed;
    }

    public function cancel(): void
    {
        if ($this->status === BookingStatus::Completed || $this->status === BookingStatus::Cancelled) {
            throw new BookingCannotBeCancelledException(
                "Cannot cancel booking with status: {$this->status->value}"
            );
        }

        $this->status = BookingStatus::Cancelled;
    }

    public function complete(): void
    {
        if ($this->status !== BookingStatus::Confirmed) {
            throw new BusinessRuleViolationException(
                "Cannot complete booking with status: {$this->status->value}"
            );
        }

        $this->status = BookingStatus::Completed;
    }

    public function attachPayment(PaymentId $paymentId): void
    {
        $this->paymentId = $paymentId;
    }
}
