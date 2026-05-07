<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Search\Specification;

use App\Infrastructure\Search\Specification\StarRatingSpec;
use PHPUnit\Framework\TestCase;

class StarRatingSpecTest extends TestCase
{
    public function testClauseFiltersOnStarsColumn(): void
    {
        $spec = new StarRatingSpec(4);

        self::assertSame('stars >= :stars_min', $spec->clause());
    }

    public function testParamsBindMinimumStars(): void
    {
        $spec = new StarRatingSpec(3);

        self::assertSame([':stars_min' => 3], $spec->params());
    }
}