<?php

declare(strict_types=1);

namespace Tests\Unit\Presentation\Controller;

use App\Application\Bus\CommandBus;
use App\Application\Command\CreateHotel\CreateHotelCommand;
use App\Domain\Entity\Hotel;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\HotelRepositoryInterface;
use App\Domain\ValueObject\Address;
use App\Domain\ValueObject\GeoPoint;
use App\Domain\ValueObject\HotelId;
use App\Domain\ValueObject\HotelName;
use App\Domain\ValueObject\StarRating;
use App\Domain\ValueObject\UserId;
use App\Presentation\Controller\HotelController;
use App\Presentation\Http\Request;
use PHPUnit\Framework\TestCase;

final class HotelControllerTest extends TestCase
{
    private HotelController $controller;
    private CommandBus $bus;

    protected function setUp(): void
    {
        $this->bus = new CommandBus();

        $hotels = $this->createMock(HotelRepositoryInterface::class);

        $this->controller = new HotelController($this->bus, $hotels);
    }

    public function testCreateReturns422WhenRequiredFieldMissing(): void
    {
        $request  = Request::create('POST', '/api/v1/hotels', ['name' => 'Grand Hotel']);
        $response = $this->controller->create($request);

        $this->assertSame(422, $response->statusCode);
        $this->assertSame('VALIDATION_ERROR', $response->data['error']['code']);
    }

    public function testCreateReturns201WithIdAndLinks(): void
    {
        $hotelId = HotelId::generate();

        $this->bus->register(CreateHotelCommand::class, new class ($hotelId) implements \App\Application\Bus\CommandHandlerInterface {
            public function __construct(private readonly HotelId $id)
            {
            }

            public function handle(\App\Application\Bus\CommandInterface $command): HotelId
            {
                return $this->id;
            }
        });

        $request = Request::create('POST', '/api/v1/hotels', $this->validHotelPayload());

        $response = $this->controller->create($request);

        $this->assertSame(201, $response->statusCode);
        $this->assertSame($hotelId->value, $response->data['id']);
        $this->assertArrayHasKey('_links', $response->data);
        $this->assertStringContainsString($hotelId->value, $response->data['_links']['self']['href']);
    }

    public function testFindByIdReturns404WhenNotFound(): void
    {
        $hotels = $this->createMock(HotelRepositoryInterface::class);
        $hotels->method('findById')->willThrowException(new EntityNotFoundException('Not found'));

        $controller = new HotelController($this->bus, $hotels);
        $request    = Request::create('GET', '/api/v1/hotels/nonexistent')
            ->withPathParams(['id' => HotelId::generate()->value]);

        $response = $controller->findById($request);

        $this->assertSame(404, $response->statusCode);
    }

    public function testFindByIdReturns200WithHotelData(): void
    {
        $hotel  = $this->makeHotel();
        $hotels = $this->createMock(HotelRepositoryInterface::class);
        $hotels->method('findById')->willReturn($hotel);

        $controller = new HotelController($this->bus, $hotels);
        $request    = Request::create('GET', '/api/v1/hotels/' . $hotel->getId()->value)
            ->withPathParams(['id' => $hotel->getId()->value]);

        $response = $controller->findById($request);

        $this->assertSame(200, $response->statusCode);
        $this->assertSame($hotel->getId()->value, $response->data['id']);
        $this->assertSame('Grand Hotel', $response->data['name']);
        $this->assertArrayHasKey('_links', $response->data);
    }

    /** @return array<string, mixed> */
    private function validHotelPayload(): array
    {
        return [
            'name'        => 'Grand Hotel',
            'description' => 'A luxury hotel',
            'street'      => '1 Rue de Rivoli',
            'city'        => 'Paris',
            'country'     => 'France',
            'postalCode'  => '75001',
            'latitude'    => 48.8566,
            'longitude'   => 2.3522,
            'stars'       => 5,
            'managerId'   => UserId::generate()->value,
        ];
    }

    private function makeHotel(): Hotel
    {
        return new Hotel(
            HotelId::generate(),
            new HotelName('Grand Hotel'),
            'A luxury hotel',
            new Address('1 Rue de Rivoli', 'Paris', 'France', '75001'),
            new GeoPoint(48.8566, 2.3522),
            new StarRating(5),
            UserId::generate(),
        );
    }
}
