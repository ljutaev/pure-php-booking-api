<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Bus;

use App\Application\Bus\QueryBus;
use App\Application\Bus\QueryHandlerInterface;
use App\Application\Bus\QueryInterface;
use App\Application\Exception\HandlerNotFoundException;
use PHPUnit\Framework\TestCase;

class QueryBusTest extends TestCase
{
    public function testDispatchCallsRegisteredHandler(): void
    {
        $query = new class () implements QueryInterface {};

        $handler = new class () implements QueryHandlerInterface {
            public bool $called = false;

            public function handle(QueryInterface $query): mixed
            {
                $this->called = true;

                return null;
            }
        };

        $bus = new QueryBus();
        $bus->register($query::class, $handler);
        $bus->dispatch($query);

        self::assertTrue($handler->called);
    }

    public function testDispatchReturnsHandlerResult(): void
    {
        $query = new class () implements QueryInterface {};

        $handler = new class () implements QueryHandlerInterface {
            public function handle(QueryInterface $query): mixed
            {
                return ['hotels' => []];
            }
        };

        $bus = new QueryBus();
        $bus->register($query::class, $handler);

        self::assertSame(['hotels' => []], $bus->dispatch($query));
    }

    public function testDispatchThrowsWhenNoHandlerRegistered(): void
    {
        $query = new class () implements QueryInterface {};
        $bus   = new QueryBus();

        $this->expectException(HandlerNotFoundException::class);
        $bus->dispatch($query);
    }

    public function testRegisterOverwritesPreviousHandler(): void
    {
        $query = new class () implements QueryInterface {};

        $first = new class () implements QueryHandlerInterface {
            public function handle(QueryInterface $query): mixed
            {
                return 'first';
            }
        };
        $second = new class () implements QueryHandlerInterface {
            public function handle(QueryInterface $query): mixed
            {
                return 'second';
            }
        };

        $bus = new QueryBus();
        $bus->register($query::class, $first);
        $bus->register($query::class, $second);

        self::assertSame('second', $bus->dispatch($query));
    }
}