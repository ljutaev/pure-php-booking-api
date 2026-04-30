<?php

declare(strict_types=1);

namespace App\Application\Command\CreateHotel;

use App\Application\Bus\CommandHandlerInterface;
use App\Application\Bus\CommandInterface;
use App\Domain\Entity\Hotel;
use App\Domain\Repository\HotelRepositoryInterface;
use App\Domain\ValueObject\Address;
use App\Domain\ValueObject\GeoPoint;
use App\Domain\ValueObject\HotelId;
use App\Domain\ValueObject\HotelName;
use App\Domain\ValueObject\StarRating;
use App\Domain\ValueObject\UserId;

final class CreateHotelHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly HotelRepositoryInterface $hotels,
    ) {
    }

    public function handle(CommandInterface $command): HotelId
    {
        assert($command instanceof CreateHotelCommand);

        $hotel = new Hotel(
            HotelId::generate(),
            new HotelName($command->name),
            $command->description,
            new Address($command->street, $command->city, $command->country, $command->postalCode),
            new GeoPoint($command->latitude, $command->longitude),
            new StarRating($command->stars),
            new UserId($command->managerId),
        );

        $this->hotels->save($hotel);

        return $hotel->getId();
    }
}
