<?php

declare(strict_types=1);

namespace App\Infrastructure\Search\Specification;

final class DateAvailabilitySpec implements HotelSqlSpecificationInterface
{
    public function __construct(
        private readonly string $checkIn,
        private readonly string $checkOut,
    ) {
    }

    public function clause(): string
    {
        return <<<'SQL'
            EXISTS (
                SELECT 1 FROM rooms r
                WHERE r.hotel_id = hotels.id
                AND r.status = 'available'
                AND r.id NOT IN (
                    SELECT b.room_id FROM bookings b
                    WHERE b.status != 'cancelled'
                    AND b.check_in < :date_check_out
                    AND b.check_out > :date_check_in
                )
            )
            SQL;
    }

    /** @return array<string, mixed> */
    public function params(): array
    {
        return [
            ':date_check_in'  => $this->checkIn,
            ':date_check_out' => $this->checkOut,
        ];
    }
}