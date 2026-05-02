<?php

declare(strict_types=1);

namespace App\Infrastructure\Container;

final class Container
{
    /** @var array<string, callable(self): mixed> */
    private array $bindings = [];

    /** @var array<string, callable(self): mixed> */
    private array $singletonFactories = [];

    /** @var array<string, mixed> */
    private array $resolved = [];

    public function bind(string $abstract, callable $factory): void
    {
        $this->bindings[$abstract] = $factory;
    }

    public function singleton(string $abstract, callable $factory): void
    {
        $this->singletonFactories[$abstract] = $factory;
        unset($this->resolved[$abstract]);
    }

    public function make(string $abstract): mixed
    {
        if (isset($this->singletonFactories[$abstract])) {
            if (!array_key_exists($abstract, $this->resolved)) {
                $this->resolved[$abstract] = ($this->singletonFactories[$abstract])($this);
            }

            return $this->resolved[$abstract];
        }

        if (isset($this->bindings[$abstract])) {
            return ($this->bindings[$abstract])($this);
        }

        throw new \RuntimeException("'{$abstract}' is not registered in the container.");
    }
}
