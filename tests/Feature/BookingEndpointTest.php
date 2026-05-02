<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Bus\CommandBus;
use App\Application\Command\BookRoom\BookRoomCommand;
use App\Application\Command\BookRoom\BookRoomHandler;
use App\Domain\Entity\Hotel;
use App\Domain\Entity\Room;
use App\Domain\Enum\RoomType;
use App\Domain\ValueObject\Address;
use App\Domain\ValueObject\Capacity;
use App\Domain\ValueObject\Currency;
use App\Domain\ValueObject\GeoPoint;
use App\Domain\ValueObject\HotelId;
use App\Domain\ValueObject\HotelName;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\RoomId;
use App\Domain\ValueObject\RoomNumber;
use App\Domain\ValueObject\StarRating;
use App\Domain\ValueObject\UserId;
use App\Infrastructure\Repository\Pdo\PdoBookingRepository;
use App\Infrastructure\Repository\Pdo\PdoHotelRepository;
use App\Infrastructure\Repository\Pdo\PdoRoomRepository;
use App\Presentation\Controller\BookingController;
use App\Presentation\Http\Request;
use App\Presentation\Http\Router;
use Tests\Integration\IntegrationTestCase;

final class BookingEndpointTest extends IntegrationTestCase
{
    private Router $router;
    private RoomId $roomId;
    private HotelId $hotelId;
    private UserId $userId;

    protected function setUp(): void
    {
        parent::setUp();

        $hotelRepo   = new PdoHotelRepository($this->pdo);
        $roomRepo    = new PdoRoomRepository($this->pdo);
        $bookingRepo = new PdoBookingRepository($this->pdo);

        $this->userId  = UserId::generate();
        $this->hotelId = HotelId::generate();
        $this->roomId  = RoomId::generate();

        $hotel = new Hotel(
            $this->hotelId,
            new HotelName('Test Hotel'),
            'Test description',
            new Address('1 Main St', 'Kyiv', 'Ukraine', '01001'),
            new GeoPoint(50.45, 30.52),
            new StarRating(4),
            $this->userId,
        );
        $hotelRepo->save($hotel);

        $room = new Room(
            $this->roomId,
            $this->hotelId,
            RoomType::Double,
            new RoomNumber('101'),
            new Capacity(2),
            new Money(15000, new Currency('USD')),
        );
        $roomRepo->save($room);

        $bus = new CommandBus();
        $bus->register(BookRoomCommand::class, new BookRoomHandler($roomRepo, $bookingRepo));

        $controller = new BookingController($bus);

        $this->router = new Router();
        $this->router->post('/api/v1/bookings', [$controller, 'create']);
    }

    public function testPostBookingReturns201WithIdAndLinks(): void
    {
        $response = $this->router->dispatch(
            Request::create('POST', '/api/v1/bookings', $this->validPayload()),
        );

        $this->assertSame(201, $response->statusCode);
        $this->assertArrayHasKey('id', $response->data);
        $this->assertArrayHasKey('_links', $response->data);
        $this->assertStringContainsString('/api/v1/bookings/', $response->data['_links']['self']['href']);
    }

    public function testPostBookingReturns422WhenBodyInvalid(): void
    {
        $response = $this->router->dispatch(
            Request::create('POST', '/api/v1/bookings', ['userId' => $this->userId->value]),
        );

        $this->assertSame(422, $response->statusCode);
    }

    public function testPostBookingReturns409WhenRoomAlreadyBooked(): void
    {
        $payload = $this->validPayload();
        $this->router->dispatch(Request::create('POST', '/api/v1/bookings', $payload));

        $response = $this->router->dispatch(Request::create('POST', '/api/v1/bookings', $payload));

        $this->assertSame(409, $response->statusCode);
    }

    public function testPostBookingReturns422WhenTooManyGuests(): void
    {
        $payload            = $this->validPayload();
        $payload['guests']  = 10;

        $response = $this->router->dispatch(
            Request::create('POST', '/api/v1/bookings', $payload),
        );

        $this->assertSame(422, $response->statusCode);
    }

    /** @return array<string, mixed> */
    private function validPayload(): array
    {
        return [
            'userId'   => $this->userId->value,
            'roomId'   => $this->roomId->value,
            'checkIn'  => '2026-11-01',
            'checkOut' => '2026-11-05',
            'guests'   => 2,
        ];
    }
}
