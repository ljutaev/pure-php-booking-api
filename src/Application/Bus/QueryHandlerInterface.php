<?php

declare(strict_types=1);

namespace App\Application\Bus;

interface QueryHandlerInterface
{
    public function handle(QueryInterface $query): mixed;
}