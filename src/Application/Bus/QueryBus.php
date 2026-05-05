<?php

declare(strict_types=1);

namespace App\Application\Bus;

use App\Application\Exception\HandlerNotFoundException;

final class QueryBus
{
    /** @var array<class-string, QueryHandlerInterface> */
    private array $handlers = [];

    /** @param class-string $queryClass */
    public function register(string $queryClass, QueryHandlerInterface $handler): void
    {
        $this->handlers[$queryClass] = $handler;
    }

    public function dispatch(QueryInterface $query): mixed
    {
        $class = $query::class;

        if (!isset($this->handlers[$class])) {
            throw new HandlerNotFoundException(
                "No handler registered for query: {$class}"
            );
        }

        return $this->handlers[$class]->handle($query);
    }
}