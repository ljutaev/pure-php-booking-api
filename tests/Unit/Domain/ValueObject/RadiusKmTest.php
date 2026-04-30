<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObject;

use App\Domain\Exception\InvalidValueObjectException;
use App\Domain\ValueObject\RadiusKm;
use PHPUnit\Framework\TestCase;

class RadiusKmTest extends TestCase
{
    public function testCreateValidRadius(): void
    {
        $radius = new RadiusKm(10.5);

        self::assertSame(10.5, $radius->value);
    }

    public function testThrowsOnZeroRadius(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        new RadiusKm(0.0);
    }

    public function testThrowsOnNegativeRadius(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        new RadiusKm(-5.0);
    }

    public function testContainsReturnsTrueWhenPointIsWithinRadius(): void
    {
        $center = new \App\Domain\ValueObject\GeoPoint(50.4501, 30.5234);
        $nearby = new \App\Domain\ValueObject\GeoPoint(50.4601, 30.5334); // ~1.3 km away
        $radius = new RadiusKm(5.0);

        self::assertTrue($radius->contains($center, $nearby));
    }

    public function testContainsReturnsFalseWhenPointIsOutsideRadius(): void
    {
        $center = new \App\Domain\ValueObject\GeoPoint(50.4501, 30.5234);
        $far    = new \App\Domain\ValueObject\GeoPoint(48.8566, 2.3522); // Paris ~2200 km away
        $radius = new RadiusKm(100.0);

        self::assertFalse($radius->contains($center, $far));
    }
}
