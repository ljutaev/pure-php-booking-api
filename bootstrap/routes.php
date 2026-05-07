<?php

declare(strict_types=1);

use App\Application\Auth\TokenServiceInterface;
use App\Application\Bus\CommandBus;
use App\Application\Bus\QueryBus;
use App\Application\Port\BookingLockInterface;
use App\Application\Port\RateLimiterInterface;
use App\Application\Query\SearchHotels\HotelSearchRepositoryInterface;
use App\Application\Query\SearchHotels\SearchHotelsHandler;
use App\Application\Query\SearchHotels\SearchHotelsQuery;
use App\Infrastructure\Lock\RedisBookingLock;
use App\Infrastructure\RateLimit\RedisRateLimiter;
use App\Infrastructure\Repository\Pdo\PdoHotelSearchRepository;
use App\Infrastructure\Repository\Redis\RedisHotelRepository;
use App\Infrastructure\Repository\Redis\RedisHotelSearchRepository;
use App\Application\Command\BookRoom\BookRoomCommand;
use App\Application\Command\BookRoom\BookRoomHandler;
use App\Application\Command\CreateHotel\CreateHotelCommand;
use App\Application\Command\CreateHotel\CreateHotelHandler;
use App\Application\Command\Login\LoginCommand;
use App\Application\Command\Login\LoginHandler;
use App\Application\Command\Logout\LogoutCommand;
use App\Application\Command\Logout\LogoutHandler;
use App\Application\Command\RefreshToken\RefreshTokenCommand;
use App\Application\Command\RefreshToken\RefreshTokenHandler;
use App\Application\Command\RegisterUser\RegisterUserCommand;
use App\Application\Command\RegisterUser\RegisterUserHandler;
use App\Application\Port\RefreshTokenStorageInterface;
use App\Application\Port\TokenBlacklistInterface;
use App\Domain\Repository\BookingRepositoryInterface;
use App\Domain\Repository\HotelRepositoryInterface;
use App\Domain\Repository\RoomRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Infrastructure\Auth\JwtTokenService;
use App\Infrastructure\Auth\RedisRefreshTokenStorage;
use App\Infrastructure\Auth\RedisTokenBlacklist;
use App\Infrastructure\Container\Container;
use App\Infrastructure\Repository\Pdo\PdoBookingRepository;
use App\Infrastructure\Repository\Pdo\PdoHotelRepository;
use App\Infrastructure\Repository\Pdo\PdoRoomRepository;
use App\Infrastructure\Repository\Pdo\PdoUserRepository;
use App\Presentation\Controller\AuthController;
use App\Presentation\Controller\BookingController;
use App\Presentation\Controller\HealthController;
use App\Presentation\Controller\HotelController;
use App\Presentation\Http\AuthMiddleware;
use App\Presentation\Http\Request;
use App\Presentation\Http\Router;

$container = new Container();

$container->singleton(\Redis::class, function (): \Redis {
    $redis = new \Redis();
    $redis->connect(
        (string) (getenv('REDIS_HOST') ?: 'redis'),
        (int) (getenv('REDIS_PORT') ?: 6379),
    );

    return $redis;
});

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
    TokenServiceInterface::class,
    fn () => new JwtTokenService(
        (string) (getenv('JWT_SECRET') ?: 'change-me-in-production-32chars!!'),
        (int) (getenv('JWT_ACCESS_TTL') ?: 900),
    ),
);

$container->singleton(
    RefreshTokenStorageInterface::class,
    fn (Container $c) => new RedisRefreshTokenStorage($c->make(\Redis::class)),
);

$container->singleton(
    TokenBlacklistInterface::class,
    fn (Container $c) => new RedisTokenBlacklist($c->make(\Redis::class)),
);

$container->singleton(
    UserRepositoryInterface::class,
    fn (Container $c) => new PdoUserRepository($c->make(\PDO::class)),
);

$container->singleton(
    HotelRepositoryInterface::class,
    fn (Container $c) => new RedisHotelRepository(
        new PdoHotelRepository($c->make(\PDO::class)),
        $c->make(\Redis::class),
    ),
);

$container->singleton(
    RoomRepositoryInterface::class,
    fn (Container $c) => new PdoRoomRepository($c->make(\PDO::class)),
);

$container->singleton(
    BookingRepositoryInterface::class,
    fn (Container $c) => new PdoBookingRepository($c->make(\PDO::class)),
);

$container->singleton(
    HotelSearchRepositoryInterface::class,
    fn (Container $c) => new RedisHotelSearchRepository(
        new PdoHotelSearchRepository($c->make(\PDO::class)),
        $c->make(\Redis::class),
    ),
);

$container->singleton(
    BookingLockInterface::class,
    fn (Container $c) => new RedisBookingLock($c->make(\Redis::class)),
);

$container->singleton(
    RateLimiterInterface::class,
    fn (Container $c) => new RedisRateLimiter(
        $c->make(\Redis::class),
        maxRequests: (int) (getenv('RATE_LIMIT_REQUESTS') ?: 60),
        windowSeconds: (int) (getenv('RATE_LIMIT_WINDOW') ?: 60),
    ),
);

$container->singleton(QueryBus::class, function (Container $c): QueryBus {
    $bus = new QueryBus();
    $bus->register(
        SearchHotelsQuery::class,
        new SearchHotelsHandler($c->make(HotelSearchRepositoryInterface::class)),
    );

    return $bus;
});

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
            $c->make(BookingLockInterface::class),
        ),
    );
    $bus->register(
        RegisterUserCommand::class,
        new RegisterUserHandler($c->make(UserRepositoryInterface::class)),
    );
    $bus->register(
        LoginCommand::class,
        new LoginHandler(
            $c->make(UserRepositoryInterface::class),
            $c->make(TokenServiceInterface::class),
            $c->make(RefreshTokenStorageInterface::class),
        ),
    );
    $bus->register(
        LogoutCommand::class,
        new LogoutHandler(
            $c->make(TokenServiceInterface::class),
            $c->make(TokenBlacklistInterface::class),
        ),
    );
    $bus->register(
        RefreshTokenCommand::class,
        new RefreshTokenHandler(
            $c->make(UserRepositoryInterface::class),
            $c->make(TokenServiceInterface::class),
            $c->make(RefreshTokenStorageInterface::class),
            $c->make(TokenBlacklistInterface::class),
        ),
    );

    return $bus;
});

$container->singleton(
    HotelController::class,
    fn (Container $c) => new HotelController(
        $c->make(CommandBus::class),
        $c->make(HotelRepositoryInterface::class),
        $c->make(QueryBus::class),
    ),
);

$container->singleton(
    BookingController::class,
    fn (Container $c) => new BookingController($c->make(CommandBus::class)),
);

$container->singleton(
    AuthController::class,
    fn (Container $c) => new AuthController(
        $c->make(CommandBus::class),
        new AuthMiddleware(
            $c->make(TokenServiceInterface::class),
            $c->make(TokenBlacklistInterface::class),
        ),
    ),
);

$router = new Router();
$health = new HealthController();

$router->get('/api/v1/health', [$health, 'health']);
$router->get('/api/v1/health/ready', [$health, 'ready']);

$router->post('/api/v1/auth/register', fn (Request $req) => $container->make(AuthController::class)->register($req));
$router->post('/api/v1/auth/login', fn (Request $req) => $container->make(AuthController::class)->login($req));
$router->post('/api/v1/auth/refresh', fn (Request $req) => $container->make(AuthController::class)->refresh($req));
$router->post('/api/v1/auth/logout', fn (Request $req) => $container->make(AuthController::class)->logout($req));

$router->get('/api/v1/hotels', fn (Request $req) => $container->make(HotelController::class)->search($req));
$router->post('/api/v1/hotels', fn (Request $req) => $container->make(HotelController::class)->create($req));
$router->get('/api/v1/hotels/{id}', fn (Request $req) => $container->make(HotelController::class)->findById($req));
$router->post('/api/v1/bookings', fn (Request $req) => $container->make(BookingController::class)->create($req));

return $router;