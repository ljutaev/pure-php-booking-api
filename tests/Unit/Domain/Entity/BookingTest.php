<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use App\Domain\Entity\Booking;
use App\Domain\Enum\BookingStatus;
use App\Domain\Exception\BookingCannotBeCancelledException;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\ValueObject\BookingId;
use App\Domain\ValueObject\Currency;
use App\Domain\ValueObject\DateRange;
use App\Domain\ValueObject\GuestCount;
use App\Domain\ValueObject\HotelId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\PaymentId;
use App\Domain\ValueObject\RoomId;
use App\Domain\ValueObject\UserId;
use PHPUnit\Framework\TestCase;

class BookingTest extends TestCase
{
    private function makeBooking(): Booking
    {
        return new Booking(
            BookingId::generate(),
            new UserId('f47ac10b-58cc-4372-a567-0e02b2c3d479'),
            new RoomId('a47ac10b-58cc-4372-a567-0e02b2c3d479'),
            new HotelId('b47ac10b-58cc-4372-a567-0e02b2c3d479'),
            new DateRange(new \DateTimeImmutable('2026-06-01'), new \DateTimeImmutable('2026-06-05')),
            new GuestCount(2),
            new Money(60000, new Currency('USD')),
            null,
        );
    }

    public function testBookingIsPendingByDefault(): void
    {
        $booking = $this->makeBooking();

        self::assertSame(BookingStatus::Pending, $booking->getStatus());
    }

    public function testConfirmChangesPendingToConfirmed(): void
    {
        $booking = $this->makeBooking();
        $booking->confirm();

        self::assertSame(BookingStatus::Confirmed, $booking->getStatus());
    }

    public function testConfirmThrowsWhenNotPending(): void
    {
        $booking = $this->makeBooking();
        $booking->confirm();

        $this->expectException(BusinessRuleViolationException::class);
        $booking->confirm();
    }

    public function testCancelFromPending(): void
    {
        $booking = $this->makeBooking();
        $booking->cancel();

        self::assertSame(BookingStatus::Cancelled, $booking->getStatus());
    }

    public function testCancelFromConfirmed(): void
    {
        $booking = $this->makeBooking();
        $booking->confirm();
        $booking->cancel();

        self::assertSame(BookingStatus::Cancelled, $booking->getStatus());
    }

    public function testCancelThrowsWhenCompleted(): void
    {
        $booking = $this->makeBooking();
        $booking->confirm();
        $booking->complete();

        $this->expectException(BookingCannotBeCancelledException::class);
        $booking->cancel();
    }

    public function testCancelThrowsWhenAlreadyCancelled(): void
    {
        $booking = $this->makeBooking();
        $booking->cancel();

        $this->expectException(BookingCannotBeCancelledException::class);
        $booking->cancel();
    }

    public function testCompleteChangesConfirmedToCompleted(): void
    {
        $booking = $this->makeBooking();
        $booking->confirm();
        $booking->complete();

        self::assertSame(BookingStatus::Completed, $booking->getStatus());
    }

    public function testCompleteThrowsWhenNotConfirmed(): void
    {
        $booking = $this->makeBooking();

        $this->expectException(BusinessRuleViolationException::class);
        $booking->complete();
    }

    public function testAttachPayment(): void
    {
        $booking   = $this->makeBooking();
        $paymentId = PaymentId::generate();
        $booking->attachPayment($paymentId);

        self::assertTrue($booking->getPaymentId()?->equals($paymentId));
    }

    public function testGetters(): void
    {
        $booking = $this->makeBooking();

        self::assertInstanceOf(BookingId::class, $booking->getId());
        self::assertSame(4, $booking->getDateRange()->nights());
        self::assertSame(60000, $booking->getTotalPrice()->amount);
        self::assertNull($booking->getPaymentId());
        self::assertNull($booking->getSpecialRequests());
        self::assertInstanceOf(\DateTimeImmutable::class, $booking->getCreatedAt());
    }
}
