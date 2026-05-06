<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Search\Specification;

use App\Infrastructure\Search\Specification\LocationSpec;
use PHPUnit\Framework\TestCase;

class LocationSpecTest extends TestCase
{
    private LocationSpec $spec;

    protected function setUp(): void
    {
        $this->spec = new LocationSpec(latitude: 50.45, longitude: 30.52, radiusKm: 10.0);
    }

    public function testClauseContainsHaversineFormula(): void
    {
        $clause = $this->spec->clause();

        self::assertStringContainsString('6371', $clause);
        self::assertStringContainsString('acos', $clause);
        self::assertStringContainsString('radians', $clause);
    }

    public function testClauseBindsCorrectPlaceholders(): void
    {
        $clause = $this->spec->clause();

        self::assertStringContainsString(':loc_lat', $clause);
        self::assertStringContainsString(':loc_lon', $clause);
        self::assertStringContainsString(':loc_radius', $clause);
    }

    public function testParamsReturnAllThreeValues(): void
    {
        $params = $this->spec->params();

        self::assertSame(50.45, $params[':loc_lat']);
        self::assertSame(30.52, $params[':loc_lon']);
        self::assertSame(10.0, $params[':loc_radius']);
    }
}