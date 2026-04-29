<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObject;

use App\Domain\Exception\InvalidValueObjectException;
use App\Domain\ValueObject\StarRating;
use PHPUnit\Framework\TestCase;

class StarRatingTest extends TestCase
{
    public function testCreateValidStarRating(): void
    {
        $rating = new StarRating(4);

        self::assertSame(4, $rating->value);
    }

    public function testThrowsOnZeroStars(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        new StarRating(0);
    }

    public function testThrowsOnSixStars(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        new StarRating(6);
    }

    public function testThrowsOnNegativeStars(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        new StarRating(-1);
    }

    public function testMinimumBoundaryIsValid(): void
    {
        $rating = new StarRating(1);

        self::assertSame(1, $rating->value);
    }

    public function testMaximumBoundaryIsValid(): void
    {
        $rating = new StarRating(5);

        self::assertSame(5, $rating->value);
    }

    public function testIsAtLeastReturnsTrueWhenEqual(): void
    {
        $rating = new StarRating(3);

        self::assertTrue($rating->isAtLeast(new StarRating(3)));
    }

    public function testIsAtLeastReturnsTrueWhenHigher(): void
    {
        $rating = new StarRating(4);

        self::assertTrue($rating->isAtLeast(new StarRating(3)));
    }

    public function testIsAtLeastReturnsFalseWhenLower(): void
    {
        $rating = new StarRating(2);

        self::assertFalse($rating->isAtLeast(new StarRating(3)));
    }
}
