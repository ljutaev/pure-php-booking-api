<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Bus\CommandBus;
use App\Application\Bus\QueryBus;
use App\Application\Command\CreateHotel\CreateHotelCommand;
use App\Application\Command\CreateHotel\CreateHotelHandler;
use App\Domain\ValueObject\UserId;
use App\Infrastructure\Repository\Pdo\PdoHotelRepository;
use App\Infrastructure\Repository\Pdo\PdoHotelSearchRepository;
use App\Application\Query\SearchHotels\SearchHotelsQuery;
use App\Application\Query\SearchHotels\SearchHotelsHandler;
use App\Presentation\Controller\HotelController;
use App\Presentation\Http\Request;
use App\Presentation\Http\Router;
use Tests\Integration\IntegrationTestCase;

final class HotelEndpointTest extends IntegrationTestCase
{
    private Router $router;

    protected function setUp(): void
    {
        parent::setUp();

        $hotelRepo  = new PdoHotelRepository($this->pdo);
        $searchRepo = new PdoHotelSearchRepository($this->pdo);

        $bus = new CommandBus();
        $bus->register(CreateHotelCommand::class, new CreateHotelHandler($hotelRepo));

        $queryBus = new QueryBus();
        $queryBus->register(SearchHotelsQuery::class, new SearchHotelsHandler($searchRepo));

        $controller = new HotelController($bus, $hotelRepo, $queryBus);

        $this->router = new Router();
        $this->router->get('/api/v1/hotels', [$controller, 'search']);
        $this->router->post('/api/v1/hotels', [$controller, 'create']);
        $this->router->get('/api/v1/hotels/{id}', [$controller, 'findById']);
    }

    public function testPostHotelReturns201WithIdAndLinks(): void
    {
        $response = $this->router->dispatch(
            Request::create('POST', '/api/v1/hotels', $this->validPayload()),
        );

        $this->assertSame(201, $response->statusCode);
        $this->assertArrayHasKey('id', $response->data);
        $this->assertArrayHasKey('_links', $response->data);
        $this->assertStringContainsString('/api/v1/hotels/', $response->data['_links']['self']['href']);
    }

    public function testPostHotelReturns422WhenBodyInvalid(): void
    {
        $response = $this->router->dispatch(
            Request::create('POST', '/api/v1/hotels', ['name' => 'Incomplete']),
        );

        $this->assertSame(422, $response->statusCode);
    }

    public function testGetHotelReturns200WithPersistedData(): void
    {
        $created = $this->router->dispatch(
            Request::create('POST', '/api/v1/hotels', $this->validPayload()),
        );

        $this->assertSame(201, $created->statusCode);
        $id = (string) $created->data['id'];

        $response = $this->router->dispatch(
            Request::create('GET', "/api/v1/hotels/{$id}")->withPathParams(['id' => $id]),
        );

        $this->assertSame(200, $response->statusCode);
        $this->assertSame($id, $response->data['id']);
        $this->assertSame('Grand Hotel', $response->data['name']);
    }

    public function testGetHotelReturns404ForNonExistentId(): void
    {
        $fakeId   = '00000000-0000-4000-a000-000000000001';
        $response = $this->router->dispatch(
            Request::create('GET', "/api/v1/hotels/{$fakeId}")->withPathParams(['id' => $fakeId]),
        );

        $this->assertSame(404, $response->statusCode);
    }

    public function testSearchReturns200WithDataAndMetaShape(): void
    {
        $this->router->dispatch(Request::create('POST', '/api/v1/hotels', $this->validPayload()));

        $response = $this->router->dispatch(Request::create('GET', '/api/v1/hotels'));

        $this->assertSame(200, $response->statusCode);
        $this->assertArrayHasKey('data', $response->data);
        $this->assertArrayHasKey('meta', $response->data);
        $this->assertArrayHasKey('total', $response->data['meta']);
        $this->assertArrayHasKey('page', $response->data['meta']);
        $this->assertArrayHasKey('per_page', $response->data['meta']);
    }

    public function testSearchIncludesCreatedHotelInResults(): void
    {
        $this->router->dispatch(Request::create('POST', '/api/v1/hotels', $this->validPayload()));

        $response = $this->router->dispatch(Request::create('GET', '/api/v1/hotels'));

        $this->assertSame(200, $response->statusCode);
        $names = array_column($response->data['data'], 'name');
        $this->assertContains('Grand Hotel', $names);
    }

    public function testSearchFiltersbyMinStarRating(): void
    {
        $low  = $this->validPayload();
        $low['name']  = 'Budget Inn';
        $low['stars'] = 2;
        $this->router->dispatch(Request::create('POST', '/api/v1/hotels', $low));

        $high  = $this->validPayload();
        $high['name']  = 'Luxury Palace';
        $high['stars'] = 5;
        $this->router->dispatch(Request::create('POST', '/api/v1/hotels', $high));

        $response = $this->router->dispatch(Request::create('GET', '/api/v1/hotels?stars=4'));

        $names = array_column($response->data['data'], 'name');
        $this->assertContains('Luxury Palace', $names);
        $this->assertNotContains('Budget Inn', $names);
    }

    public function testSearchPaginatesResults(): void
    {
        $this->router->dispatch(Request::create('POST', '/api/v1/hotels', array_merge($this->validPayload(), ['name' => 'Page Hotel A'])));
        $this->router->dispatch(Request::create('POST', '/api/v1/hotels', array_merge($this->validPayload(), ['name' => 'Page Hotel B'])));
        $this->router->dispatch(Request::create('POST', '/api/v1/hotels', array_merge($this->validPayload(), ['name' => 'Page Hotel C'])));

        $page1 = $this->router->dispatch(Request::create('GET', '/api/v1/hotels?per_page=2&page=1'));
        $page2 = $this->router->dispatch(Request::create('GET', '/api/v1/hotels?per_page=2&page=2'));

        $this->assertSame(200, $page1->statusCode);
        $this->assertCount(2, $page1->data['data']);
        $this->assertSame(1, $page1->data['meta']['page']);
        $this->assertSame(2, $page2->data['meta']['page']);
        $this->assertGreaterThanOrEqual(1, count($page2->data['data']));
    }

    public function testSearchItemHasHateoasLinks(): void
    {
        $this->router->dispatch(Request::create('POST', '/api/v1/hotels', $this->validPayload()));

        $response = $this->router->dispatch(Request::create('GET', '/api/v1/hotels'));

        $this->assertNotEmpty($response->data['data']);
        $first = $response->data['data'][0];
        $this->assertArrayHasKey('_links', $first);
        $this->assertStringContainsString('/api/v1/hotels/', $first['_links']['self']['href']);
    }

    /** @return array<string, mixed> */
    private function validPayload(): array
    {
        return [
            'name'        => 'Grand Hotel',
            'description' => 'A luxury hotel in Paris',
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
}
