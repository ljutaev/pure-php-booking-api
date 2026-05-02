<?php

declare(strict_types=1);

use App\Application\Bus\CommandBus;
use App\Application\Command\BookRoom\BookRoomCommand;
use App\Application\Command\BookRoom\BookRoomHandler;
use App\Application\Command\CreateHotel\CreateHotelCommand;
use App\Application\Command\CreateHotel\CreateHotelHandler;
use App\Domain\Repository\BookingRepositoryInterface;
use App\Domain\Repository\HotelRepositoryInterface;
use App\Domain\Repository\RoomRepositoryInterface;
use App\Infrastructure\Container\Container;
use App\Infrastructure\Repository\Pdo\PdoBookingRepository;
use App\Infrastructure\Repository\Pdo\PdoHotelRepository;
use App\Infrastructure\Repository\Pdo\PdoRoomRepository;
use App\Presentation\Controller\BookingController;
use App\Presentation\Controller\HealthController;
use App\Presentation\Controller\HotelController;
use App\Presentation\Http\Request;
use App\Presentation\Http\Router;

$container = new Container();

$container->singleton(\PDO::class, function (): \PDO {
    $host = (string) (getenv('DB_HOST') ?: 'postgres');
    $port = (string) (getenv('DB_PORT') ?: '5432');
    $name = (string) (getenv('DB_NAME') ?: 'hotel_booking');
    $user = (string) (getenv('DB_USER') ?: 'hotel_user');
    $pass = (string) (getenv('DB_PASSWORD') ?: 'secret');

    return new \PDO(
        "pgsql:host={$host};port={$port};dbname={$name}",
        $user,
        $pass,
        [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC],
    );
});

$container->singleton(
    HotelRepositoryInterface::class,
    fn (Container $c) => new PdoHotelRepository($c->make(\PDO::class)),
);

$container->singleton(
    RoomRepositoryInterface::class,
    fn (Container $c) => new PdoRoomRepository($c->make(\PDO::class)),
);

$container->singleton(
    BookingRepositoryInterface::class,
    fn (Container $c) => new PdoBookingRepository($c->make(\PDO::class)),
);

$container->singleton(CommandBus::class, function (Container $c): CommandBus {
    $bus = new CommandBus();
    $bus->register(
        CreateHotelCommand::class,
        new CreateHotelHandler($c->make(HotelRepositoryInterface::class)),
    );
    $bus->register(
        BookRoomCommand::class,
        new BookRoomHandler(
            $c->make(RoomRepositoryInterface::class),
            $c->make(BookingRepositoryInterface::class),
        ),
    );

    return $bus;
});

$container->singleton(
    HotelController::class,
    fn (Container $c) => new HotelController(
        $c->make(CommandBus::class),
        $c->make(HotelRepositoryInterface::class),
    ),
);

$container->singleton(
    BookingController::class,
    fn (Container $c) => new BookingController($c->make(CommandBus::class)),
);

$router = new Router();
$health = new HealthController();

$router->get('/api/v1/health', [$health, 'health']);
$router->get('/api/v1/health/ready', [$health, 'ready']);

$router->post('/api/v1/hotels', fn (Request $req) => $container->make(HotelController::class)->create($req));
$router->get('/api/v1/hotels/{id}', fn (Request $req) => $container->make(HotelController::class)->findById($req));
$router->post('/api/v1/bookings', fn (Request $req) => $container->make(BookingController::class)->create($req));

return $router;