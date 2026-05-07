<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Search\Specification;

use App\Infrastructure\Search\Specification\PriceRangeSpec;
use PHPUnit\Framework\TestCase;

class PriceRangeSpecTest extends TestCase
{
    public function testClauseWithBothBounds(): void
    {
        $spec   = new PriceRangeSpec(min: 50.0, max: 200.0);
        $clause = $spec->clause();

        self::assertStringContainsString(':price_min', $clause);
        self::assertStringContainsString(':price_max', $clause);
        self::assertStringContainsString('EXISTS', $clause);
    }

    public function testClauseWithMinOnly(): void
    {
        $spec   = new PriceRangeSpec(min: 50.0, max: null);
        $clause = $spec->clause();

        self::assertStringContainsString(':price_min', $clause);
        self::assertStringNotContainsString(':price_max', $clause);
    }

    public function testClauseWithMaxOnly(): void
    {
        $spec   = new PriceRangeSpec(min: null, max: 200.0);
        $clause = $spec->clause();

        self::assertStringNotContainsString(':price_min', $clause);
        self::assertStringContainsString(':price_max', $clause);
    }

    public function testParamsWithBothBounds(): void
    {
        $spec   = new PriceRangeSpec(min: 50.0, max: 200.0);
        $params = $spec->params();

        self::assertSame(50.0, $params[':price_min']);
        self::assertSame(200.0, $params[':price_max']);
    }

    public function testParamsWithMinOnly(): void
    {
        $spec   = new PriceRangeSpec(min: 50.0, max: null);
        $params = $spec->params();

        self::assertArrayHasKey(':price_min', $params);
        self::assertArrayNotHasKey(':price_max', $params);
    }
}
