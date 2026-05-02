<?php

declare(strict_types=1);

namespace Tests\Unit\Presentation\Controller;

use App\Application\Bus\CommandBus;
use App\Application\Bus\CommandHandlerInterface;
use App\Application\Bus\CommandInterface;
use App\Application\Command\BookRoom\BookRoomCommand;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Exception\RoomNotAvailableException;
use App\Domain\ValueObject\BookingId;
use App\Domain\ValueObject\RoomId;
use App\Domain\ValueObject\UserId;
use App\Presentation\Controller\BookingController;
use App\Presentation\Http\Request;
use PHPUnit\Framework\TestCase;

final class BookingControllerTest extends TestCase
{
    public function testCreateReturns422WhenRequiredFieldMissing(): void
    {
        $controller = new BookingController(new CommandBus());
        $request    = Request::create('POST', '/api/v1/bookings', ['userId' => 'user-123']);

        $response = $controller->create($request);

        $this->assertSame(422, $response->statusCode);
        $this->assertSame('VALIDATION_ERROR', $response->data['error']['code']);
    }

    public function testCreateReturns201WithIdAndLinks(): void
    {
        $bookingId = BookingId::generate();

        $bus = new CommandBus();
        $bus->register(BookRoomCommand::class, new class ($bookingId) implements CommandHandlerInterface {
            public function __construct(private readonly BookingId $id)
            {
            }

            public function handle(CommandInterface $command): BookingId
            {
                return $this->id;
            }
        });

        $controller = new BookingController($bus);
        $request    = Request::create('POST', '/api/v1/bookings', $this->validBookingPayload());

        $response = $controller->create($request);

        $this->assertSame(201, $response->statusCode);
        $this->assertSame($bookingId->value, $response->data['id']);
        $this->assertArrayHasKey('_links', $response->data);
    }

    public function testCreateReturns404WhenRoomNotFound(): void
    {
        $bus = new CommandBus();
        $bus->register(BookRoomCommand::class, new class () implements CommandHandlerInterface {
            public function handle(CommandInterface $command): never
            {
                throw new EntityNotFoundException('Room not found');
            }
        });

        $controller = new BookingController($bus);
        $response   = $controller->create(Request::create('POST', '/api/v1/bookings', $this->validBookingPayload()));

        $this->assertSame(404, $response->statusCode);
    }

    public function testCreateReturns409WhenRoomNotAvailable(): void
    {
        $bus = new CommandBus();
        $bus->register(BookRoomCommand::class, new class () implements CommandHandlerInterface {
            public function handle(CommandInterface $command): never
            {
                throw new RoomNotAvailableException('Room already booked');
            }
        });

        $controller = new BookingController($bus);
        $response   = $controller->create(Request::create('POST', '/api/v1/bookings', $this->validBookingPayload()));

        $this->assertSame(409, $response->statusCode);
    }

    /** @return array<string, mixed> */
    private function validBookingPayload(): array
    {
        return [
            'userId'   => UserId::generate()->value,
            'roomId'   => RoomId::generate()->value,
            'checkIn'  => '2026-09-01',
            'checkOut' => '2026-09-05',
            'guests'   => 2,
        ];
    }
}
