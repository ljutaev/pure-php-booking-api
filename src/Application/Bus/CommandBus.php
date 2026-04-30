<?php

declare(strict_types=1);

namespace App\Application\Bus;

use App\Application\Exception\HandlerNotFoundException;

final class CommandBus
{
    /** @var array<class-string, CommandHandlerInterface> */
    private array $handlers = [];

    /** @param class-string $commandClass */
    public function register(string $commandClass, CommandHandlerInterface $handler): void
    {
        $this->handlers[$commandClass] = $handler;
    }

    public function dispatch(CommandInterface $command): mixed
    {
        $class = $command::class;

        if (!isset($this->handlers[$class])) {
            throw new HandlerNotFoundException(
                "No handler registered for command: {$class}"
            );
        }

        return $this->handlers[$class]->handle($command);
    }
}
