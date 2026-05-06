<?php

declare(strict_types=1);

namespace App\Infrastructure\Search\Specification;

interface HotelSqlSpecificationInterface
{
    public function clause(): string;

    /** @return array<string, mixed> */
    public function params(): array;
}