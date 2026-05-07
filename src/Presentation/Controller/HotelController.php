<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\Bus\CommandBus;
use App\Application\Bus\QueryBus;
use App\Application\Command\CreateHotel\CreateHotelCommand;
use App\Application\Query\SearchHotels\HotelListItem;
use App\Application\Query\SearchHotels\SearchHotelsQuery;
use App\Application\Query\SearchHotels\SearchHotelsResult;
use App\Application\Search\HotelSearchCriteria;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\HotelRepositoryInterface;
use App\Domain\ValueObject\HotelId;
use App\Presentation\Http\JsonResponse;
use App\Presentation\Http\Request;
use App\Presentation\Http\RequestValidator;

final class HotelController
{
    public function __construct(
        private readonly CommandBus $bus,
        private readonly HotelRepositoryInterface $hotels,
        private readonly QueryBus $queryBus,
    ) {
    }

    public function search(Request $request): JsonResponse
    {
        $q = $request->query;

        $criteria = new HotelSearchCriteria(
            latitude:  isset($q['lat'])        ? (float) $q['lat']        : null,
            longitude: isset($q['lon'])        ? (float) $q['lon']        : null,
            radiusKm:  isset($q['radius'])     ? (float) $q['radius']     : null,
            checkIn:   isset($q['check_in'])   ? (string) $q['check_in']  : null,
            checkOut:  isset($q['check_out'])  ? (string) $q['check_out'] : null,
            priceMin:  isset($q['price_min'])  ? (float) $q['price_min']  : null,
            priceMax:  isset($q['price_max'])  ? (float) $q['price_max']  : null,
            stars:     isset($q['stars'])      ? (int) $q['stars']        : null,
            page:      isset($q['page'])       ? max(1, (int) $q['page']) : 1,
            perPage:   isset($q['per_page'])   ? min(100, max(1, (int) $q['per_page'])) : 20,
        );

        /** @var SearchHotelsResult $result */
        $result = $this->queryBus->dispatch(new SearchHotelsQuery($criteria));

        return JsonResponse::ok([
            'data' => array_map($this->serializeItem(...), $result->items),
            'meta' => [
                'total'    => $result->total,
                'page'     => $result->page,
                'per_page' => $result->perPage,
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private function serializeItem(HotelListItem $item): array
    {
        return [
            'id'      => $item->id,
            'name'    => $item->name,
            'city'    => $item->city,
            'country' => $item->country,
            'location' => [
                'latitude'  => $item->latitude,
                'longitude' => $item->longitude,
            ],
            'stars'              => $item->stars,
            'distance_km'        => $item->distanceKm,
            'min_price_per_night' => $item->minPricePerNight,
            '_links' => [
                'self' => ['href' => "/api/v1/hotels/{$item->id}"],
            ],
        ];
    }

    public function create(Request $request): JsonResponse
    {
        $errors = RequestValidator::validate($request->body, [
            'name'        => 'required|string',
            'description' => 'required|string',
            'street'      => 'required|string',
            'city'        => 'required|string',
            'country'     => 'required|string',
            'postalCode'  => 'required|string',
            'latitude'    => 'required|float',
            'longitude'   => 'required|float',
            'stars'       => 'required|int',
            'managerId'   => 'required|string',
        ]);

        if ($errors !== null) {
            return JsonResponse::unprocessableEntity($errors);
        }

        $command = new CreateHotelCommand(
            (string) $request->body['name'],
            (string) $request->body['description'],
            (string) $request->body['street'],
            (string) $request->body['city'],
            (string) $request->body['country'],
            (string) $request->body['postalCode'],
            (float) $request->body['latitude'],
            (float) $request->body['longitude'],
            (int) $request->body['stars'],
            (string) $request->body['managerId'],
        );

        $hotelId = $this->bus->dispatch($command);
        assert($hotelId instanceof HotelId);

        return JsonResponse::created([
            'id'     => $hotelId->value,
            '_links' => [
                'self' => ['href' => "/api/v1/hotels/{$hotelId->value}"],
            ],
        ]);
    }

    public function findById(Request $request): JsonResponse
    {
        $id = $request->pathParams['id'] ?? '';

        try {
            $hotel = $this->hotels->findById(new HotelId($id));
        } catch (EntityNotFoundException $e) {
            return JsonResponse::notFound($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return JsonResponse::notFound('Hotel not found');
        }

        return JsonResponse::ok([
            'id'          => $hotel->getId()->value,
            'name'        => $hotel->getName()->value,
            'description' => $hotel->getDescription(),
            'address'     => [
                'street'     => $hotel->getAddress()->street,
                'city'       => $hotel->getAddress()->city,
                'country'    => $hotel->getAddress()->country,
                'postalCode' => $hotel->getAddress()->postalCode,
            ],
            'location' => [
                'latitude'  => $hotel->getLocation()->latitude,
                'longitude' => $hotel->getLocation()->longitude,
            ],
            'stars'     => $hotel->getStarRating()->value,
            'status'    => $hotel->getStatus()->value,
            'managerId' => $hotel->getManagerId()->value,
            '_links'    => [
                'self' => ['href' => "/api/v1/hotels/{$hotel->getId()->value}"],
            ],
        ]);
    }
}
