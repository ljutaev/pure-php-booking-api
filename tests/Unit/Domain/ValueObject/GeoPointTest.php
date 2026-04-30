<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObject;

use App\Domain\Exception\InvalidValueObjectException;
use App\Domain\ValueObject\GeoPoint;
use PHPUnit\Framework\TestCase;

class GeoPointTest extends TestCase
{
    public function testCreateValidGeoPoint(): void
    {
        $point = new GeoPoint(48.8566, 2.3522);

        self::assertSame(48.8566, $point->latitude);
        self::assertSame(2.3522, $point->longitude);
    }

    public function testThrowsOnLatitudeTooHigh(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        new GeoPoint(90.1, 0.0);
    }

    public function testThrowsOnLatitudeTooLow(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        new GeoPoint(-90.1, 0.0);
    }

    public function testThrowsOnLongitudeTooHigh(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        new GeoPoint(0.0, 180.1);
    }

    public function testThrowsOnLongitudeTooLow(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        new GeoPoint(0.0, -180.1);
    }

    public function testBoundaryValuesAreValid(): void
    {
        $point = new GeoPoint(90.0, 180.0);

        self::assertSame(90.0, $point->latitude);
        self::assertSame(180.0, $point->longitude);
    }

    public function testDistanceToSamePointIsZero(): void
    {
        $point = new GeoPoint(48.8566, 2.3522);

        self::assertSame(0.0, $point->distanceTo($point));
    }

    public function testDistanceBetweenKyivAndParis(): void
    {
        $kyiv  = new GeoPoint(50.4501, 30.5234);
        $paris = new GeoPoint(48.8566, 2.3522);

        $distance = $kyiv->distanceTo($paris);

        // ~2020 km, tolerance ±50 km
        self::assertGreaterThan(1970.0, $distance);
        self::assertLessThan(2070.0, $distance);
    }

    public function testDistanceIsSymmetric(): void
    {
        $kyiv  = new GeoPoint(50.4501, 30.5234);
        $paris = new GeoPoint(48.8566, 2.3522);

        self::assertSame($kyiv->distanceTo($paris), $paris->distanceTo($kyiv));
    }
}
