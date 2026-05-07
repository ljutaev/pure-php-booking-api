<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Search\Specification;

use App\Infrastructure\Search\Specification\DateAvailabilitySpec;
use PHPUnit\Framework\TestCase;

class DateAvailabilitySpecTest extends TestCase
{
    private DateAvailabilitySpec $spec;

    protected function setUp(): void
    {
        $this->spec = new DateAvailabilitySpec(checkIn: '2026-06-01', checkOut: '2026-06-05');
    }

    public function testClauseChecksRoomAvailabilityViaSubquery(): void
    {
        $clause = $this->spec->clause();

        self::assertStringContainsString('EXISTS', $clause);
        self::assertStringContainsString('bookings', $clause);
        self::assertStringContainsString(':date_check_in', $clause);
        self::assertStringContainsString(':date_check_out', $clause);
    }

    public function testClauseExcludesCancelledBookings(): void
    {
        self::assertStringContainsString("status != 'cancelled'", $this->spec->clause());
    }

    public function testParamsReturnDates(): void
    {
        $params = $this->spec->params();

        self::assertSame('2026-06-01', $params[':date_check_in']);
        self::assertSame('2026-06-05', $params[':date_check_out']);
    }
}