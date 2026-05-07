<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Auth\TokenServiceInterface;
use App\Application\Bus\CommandBus;
use App\Application\Command\Login\LoginCommand;
use App\Application\Command\Login\LoginHandler;
use App\Application\Command\Logout\LogoutCommand;
use App\Application\Command\Logout\LogoutHandler;
use App\Application\Command\RefreshToken\RefreshTokenCommand;
use App\Application\Command\RefreshToken\RefreshTokenHandler;
use App\Application\Command\RegisterUser\RegisterUserCommand;
use App\Application\Command\RegisterUser\RegisterUserHandler;
use App\Infrastructure\Auth\JwtTokenService;
use App\Infrastructure\Auth\RedisRefreshTokenStorage;
use App\Infrastructure\Auth\RedisTokenBlacklist;
use App\Infrastructure\Repository\Pdo\PdoUserRepository;
use App\Presentation\Controller\AuthController;
use App\Presentation\Http\AuthMiddleware;
use App\Presentation\Http\Request;
use App\Presentation\Http\Router;
use Tests\Integration\IntegrationTestCase;

class AuthEndpointTest extends IntegrationTestCase
{
    private Router $router;
    private TokenServiceInterface $tokenService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tokenService = new JwtTokenService('test-secret-key-at-least-32-chars!', 900);

        $userRepo    = new PdoUserRepository($this->pdo);
        $refreshStorage = new RedisRefreshTokenStorage($this->redis);
        $blacklist   = new RedisTokenBlacklist($this->redis);

        $bus = new CommandBus();
        $bus->register(RegisterUserCommand::class, new RegisterUserHandler($userRepo));
        $bus->register(LoginCommand::class, new LoginHandler($userRepo, $this->tokenService, $refreshStorage));
        $bus->register(LogoutCommand::class, new LogoutHandler($this->tokenService, $blacklist));
        $bus->register(RefreshTokenCommand::class, new RefreshTokenHandler($userRepo, $this->tokenService, $refreshStorage, $blacklist));

        $authMiddleware = new AuthMiddleware($this->tokenService, $blacklist);
        $authController = new AuthController($bus, $authMiddleware);

        $this->router = new Router();
        $this->router->post('/api/v1/auth/register', [$authController, 'register']);
        $this->router->post('/api/v1/auth/login', [$authController, 'login']);
        $this->router->post('/api/v1/auth/refresh', [$authController, 'refresh']);
        $this->router->post('/api/v1/auth/logout', [$authController, 'logout']);
    }

    public function testRegisterReturns201WithUserId(): void
    {
        $req      = Request::create('POST', '/api/v1/auth/register', [
            'email'      => 'alice@example.com',
            'password'   => 'secret123',
            'first_name' => 'Alice',
            'last_name'  => 'Smith',
        ]);
        $response = $this->router->dispatch($req);

        self::assertSame(201, $response->statusCode);
        self::assertArrayHasKey('data', $response->data);
        self::assertArrayHasKey('id', $response->data['data']);
    }

    public function testRegisterReturns422WhenEmailMissing(): void
    {
        $req      = Request::create('POST', '/api/v1/auth/register', [
            'password'   => 'secret123',
            'first_name' => 'Alice',
            'last_name'  => 'Smith',
        ]);
        $response = $this->router->dispatch($req);

        self::assertSame(422, $response->statusCode);
    }

    public function testRegisterReturns409WhenEmailDuplicate(): void
    {
        $body = [
            'email'      => 'bob@example.com',
            'password'   => 'pass',
            'first_name' => 'Bob',
            'last_name'  => 'Jones',
        ];

        $this->router->dispatch(Request::create('POST', '/api/v1/auth/register', $body));
        $response = $this->router->dispatch(Request::create('POST', '/api/v1/auth/register', $body));

        self::assertSame(409, $response->statusCode);
    }

    public function testLoginReturns200WithTokens(): void
    {
        $this->router->dispatch(Request::create('POST', '/api/v1/auth/register', [
            'email' => 'carol@example.com', 'password' => 'pass123',
            'first_name' => 'Carol', 'last_name' => 'White',
        ]));

        $response = $this->router->dispatch(Request::create('POST', '/api/v1/auth/login', [
            'email' => 'carol@example.com', 'password' => 'pass123',
        ]));

        self::assertSame(200, $response->statusCode);
        self::assertArrayHasKey('access_token', $response->data['data']);
        self::assertArrayHasKey('refresh_token', $response->data['data']);
        self::assertArrayHasKey('user_id', $response->data['data']);
    }

    public function testLoginReturns401WhenPasswordWrong(): void
    {
        $this->router->dispatch(Request::create('POST', '/api/v1/auth/register', [
            'email' => 'dave@example.com', 'password' => 'correct',
            'first_name' => 'Dave', 'last_name' => 'Brown',
        ]));

        $response = $this->router->dispatch(Request::create('POST', '/api/v1/auth/login', [
            'email' => 'dave@example.com', 'password' => 'wrong',
        ]));

        self::assertSame(401, $response->statusCode);
    }

    public function testRefreshReturns200WithNewTokens(): void
    {
        $this->router->dispatch(Request::create('POST', '/api/v1/auth/register', [
            'email' => 'eve@example.com', 'password' => 'pass',
            'first_name' => 'Eve', 'last_name' => 'Black',
        ]));

        $loginResp = $this->router->dispatch(Request::create('POST', '/api/v1/auth/login', [
            'email' => 'eve@example.com', 'password' => 'pass',
        ]));

        $data = $loginResp->data['data'];
        self::assertIsArray($data);

        $response = $this->router->dispatch(Request::create('POST', '/api/v1/auth/refresh', [
            'user_id'       => $data['user_id'],
            'refresh_token' => $data['refresh_token'],
            'access_token'  => $data['access_token'],
        ]));

        self::assertSame(200, $response->statusCode);
        self::assertArrayHasKey('access_token', $response->data['data']);
    }

    public function testLogoutReturns204(): void
    {
        $this->router->dispatch(Request::create('POST', '/api/v1/auth/register', [
            'email' => 'frank@example.com', 'password' => 'pass',
            'first_name' => 'Frank', 'last_name' => 'Green',
        ]));

        $loginResp = $this->router->dispatch(Request::create('POST', '/api/v1/auth/login', [
            'email' => 'frank@example.com', 'password' => 'pass',
        ]));

        $data = $loginResp->data['data'];
        self::assertIsArray($data);

        $req      = Request::create('POST', '/api/v1/auth/logout', []);
        $req      = $req->withHeader('Authorization', 'Bearer ' . $data['access_token']);
        $response = $this->router->dispatch($req);

        self::assertSame(204, $response->statusCode);
    }
}
