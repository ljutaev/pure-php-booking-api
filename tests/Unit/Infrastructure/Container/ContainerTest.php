<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Container;

use App\Infrastructure\Container\Container;
use PHPUnit\Framework\TestCase;

final class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    public function testBindReturnsNewInstanceEachCall(): void
    {
        $this->container->bind('counter', fn () => new \stdClass());

        $a = $this->container->make('counter');
        $b = $this->container->make('counter');

        $this->assertNotSame($a, $b);
    }

    public function testSingletonReturnsSameInstanceEachCall(): void
    {
        $this->container->singleton('service', fn () => new \stdClass());

        $a = $this->container->make('service');
        $b = $this->container->make('service');

        $this->assertSame($a, $b);
    }

    public function testFactoryReceivesContainerInstance(): void
    {
        $this->container->singleton('dep', fn () => new \stdClass());
        $receivedContainer = null;

        $this->container->bind('consumer', function (Container $c) use (&$receivedContainer) {
            $receivedContainer = $c;

            return new \stdClass();
        });

        $this->container->make('consumer');

        $this->assertSame($this->container, $receivedContainer);
    }

    public function testSingletonCanResolveDependency(): void
    {
        $dep = new \stdClass();
        $dep->name = 'dependency';

        $this->container->singleton('dep', fn () => $dep);
        $this->container->singleton('service', function (Container $c) {
            $resolved = $c->make('dep');
            $obj = new \stdClass();
            $obj->dep = $resolved;

            return $obj;
        });

        /** @var \stdClass $service */
        $service = $this->container->make('service');

        $this->assertSame($dep, $service->dep);
    }

    public function testMakeThrowsForUnregisteredAbstract(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('not registered');

        $this->container->make('unknown.service');
    }

    public function testBindOverwritesPreviousBinding(): void
    {
        $this->container->bind('svc', fn () => 'first');
        $this->container->bind('svc', fn () => 'second');

        $this->assertSame('second', $this->container->make('svc'));
    }

    public function testSingletonIsLazilyInitialized(): void
    {
        $called = false;

        $this->container->singleton('lazy', function () use (&$called) {
            $called = true;

            return new \stdClass();
        });

        $this->assertFalse($called);

        $this->container->make('lazy');

        $this->assertTrue($called);
    }
}
