<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Bus;

use App\Application\Bus\CommandBus;
use App\Application\Bus\CommandHandlerInterface;
use App\Application\Bus\CommandInterface;
use App\Application\Exception\HandlerNotFoundException;
use PHPUnit\Framework\TestCase;

class CommandBusTest extends TestCase
{
    public function testDispatchCallsRegisteredHandler(): void
    {
        $command = new class () implements CommandInterface {};

        $handler = new class () implements CommandHandlerInterface {
            public bool $called = false;

            public function handle(CommandInterface $command): mixed
            {
                $this->called = true;

                return null;
            }
        };

        $bus = new CommandBus();
        $bus->register($command::class, $handler);
        $bus->dispatch($command);

        self::assertTrue($handler->called);
    }

    public function testDispatchReturnsHandlerResult(): void
    {
        $command = new class () implements CommandInterface {};

        $handler = new class () implements CommandHandlerInterface {
            public function handle(CommandInterface $command): mixed
            {
                return 'result';
            }
        };

        $bus = new CommandBus();
        $bus->register($command::class, $handler);

        self::assertSame('result', $bus->dispatch($command));
    }

    public function testDispatchThrowsWhenNoHandlerRegistered(): void
    {
        $command = new class () implements CommandInterface {};
        $bus     = new CommandBus();

        $this->expectException(HandlerNotFoundException::class);
        $bus->dispatch($command);
    }

    public function testRegisterOverwritesPreviousHandler(): void
    {
        $command = new class () implements CommandInterface {};

        $first = new class () implements CommandHandlerInterface {
            public function handle(CommandInterface $command): mixed
            {
                return 'first';
            }
        };
        $second = new class () implements CommandHandlerInterface {
            public function handle(CommandInterface $command): mixed
            {
                return 'second';
            }
        };

        $bus = new CommandBus();
        $bus->register($command::class, $first);
        $bus->register($command::class, $second);

        self::assertSame('second', $bus->dispatch($command));
    }
}
