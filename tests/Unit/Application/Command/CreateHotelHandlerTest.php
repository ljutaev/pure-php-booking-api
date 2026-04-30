<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Command;

use App\Application\Command\CreateHotel\CreateHotelCommand;
use App\Application\Command\CreateHotel\CreateHotelHandler;
use App\Domain\Exception\InvalidValueObjectException;
use App\Domain\Repository\HotelRepositoryInterface;
use App\Domain\ValueObject\HotelId;
use PHPUnit\Framework\TestCase;

class CreateHotelHandlerTest extends TestCase
{
    private function makeCommand(): CreateHotelCommand
    {
        return new CreateHotelCommand(
            name: 'Grand Palace',
            description: 'Luxury hotel',
            street: 'Main St 1',
            city: 'Kyiv',
            country: 'UA',
            postalCode: '01001',
            latitude: 50.45,
            longitude: 30.52,
            stars: 5,
            managerId: 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
        );
    }

    public function testHandleReturnsHotelId(): void
    {
        $repo    = $this->createMock(HotelRepositoryInterface::class);
        $handler = new CreateHotelHandler($repo);

        $result = $handler->handle($this->makeCommand());

        self::assertInstanceOf(HotelId::class, $result);
    }

    public function testHandleSavesHotelToRepository(): void
    {
        $repo = $this->createMock(HotelRepositoryInterface::class);
        $repo->expects($this->once())->method('save');

        $handler = new CreateHotelHandler($repo);
        $handler->handle($this->makeCommand());
    }

    public function testHandleWithInvalidStarsThrows(): void
    {
        $repo    = $this->createMock(HotelRepositoryInterface::class);
        $handler = new CreateHotelHandler($repo);

        $command = new CreateHotelCommand(
            name: 'Name',
            description: 'Desc',
            street: 'St 1',
            city: 'City',
            country: 'UA',
            postalCode: '00000',
            latitude: 50.0,
            longitude: 30.0,
            stars: 6,
            managerId: 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
        );

        $this->expectException(InvalidValueObjectException::class);
        $handler->handle($command);
    }
}
