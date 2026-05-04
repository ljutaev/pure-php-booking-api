<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\Auth\TokenPair;
use App\Application\Bus\CommandBus;
use App\Application\Command\Login\LoginCommand;
use App\Application\Command\Logout\LogoutCommand;
use App\Application\Command\RefreshToken\RefreshTokenCommand;
use App\Application\Command\RegisterUser\RegisterUserCommand;
use App\Domain\Exception\DuplicateEmailException;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Exception\InvalidCredentialsException;
use App\Domain\Exception\InvalidTokenException;
use App\Domain\ValueObject\UserId;
use App\Presentation\Http\AuthMiddleware;
use App\Presentation\Http\JsonResponse;
use App\Presentation\Http\Request;
use App\Presentation\Http\RequestValidator;

final class AuthController
{
    public function __construct(
        private readonly CommandBus $bus,
        private readonly AuthMiddleware $authMiddleware,
    ) {
    }

    public function register(Request $request): JsonResponse
    {
        $errors = RequestValidator::validate($request->body, [
            'email'      => 'required|string',
            'password'   => 'required|string',
            'first_name' => 'required|string',
            'last_name'  => 'required|string',
        ]);

        if ($errors !== null) {
            return JsonResponse::unprocessableEntity($errors);
        }

        try {
            /** @var UserId $id */
            $id = $this->bus->dispatch(new RegisterUserCommand(
                email: (string) $request->body['email'],
                password: (string) $request->body['password'],
                firstName: (string) $request->body['first_name'],
                lastName: (string) $request->body['last_name'],
            ));

            return JsonResponse::created([
                'data' => [
                    'id'     => $id->value,
                    '_links' => ['self' => ['href' => "/api/v1/users/{$id->value}"]],
                ],
            ]);
        } catch (DuplicateEmailException $e) {
            return JsonResponse::conflict($e->getMessage());
        }
    }

    public function login(Request $request): JsonResponse
    {
        $errors = RequestValidator::validate($request->body, [
            'email'    => 'required|string',
            'password' => 'required|string',
        ]);

        if ($errors !== null) {
            return JsonResponse::unprocessableEntity($errors);
        }

        try {
            /** @var TokenPair $pair */
            $pair = $this->bus->dispatch(new LoginCommand(
                email: (string) $request->body['email'],
                password: (string) $request->body['password'],
            ));

            $claims = $this->claimsFromToken($pair->accessToken);

            return JsonResponse::ok([
                'data' => [
                    'access_token'  => $pair->accessToken,
                    'refresh_token' => $pair->refreshToken,
                    'expires_in'    => $pair->expiresIn,
                    'user_id'       => $claims['sub'] ?? '',
                ],
            ]);
        } catch (EntityNotFoundException | InvalidCredentialsException) {
            return JsonResponse::unauthorized('Invalid email or password');
        }
    }

    public function refresh(Request $request): JsonResponse
    {
        $errors = RequestValidator::validate($request->body, [
            'user_id'       => 'required|string',
            'refresh_token' => 'required|string',
            'access_token'  => 'required|string',
        ]);

        if ($errors !== null) {
            return JsonResponse::unprocessableEntity($errors);
        }

        try {
            /** @var TokenPair $pair */
            $pair = $this->bus->dispatch(new RefreshTokenCommand(
                userId: (string) $request->body['user_id'],
                refreshToken: (string) $request->body['refresh_token'],
                accessToken: (string) $request->body['access_token'],
            ));

            $claims = $this->claimsFromToken($pair->accessToken);

            return JsonResponse::ok([
                'data' => [
                    'access_token'  => $pair->accessToken,
                    'refresh_token' => $pair->refreshToken,
                    'expires_in'    => $pair->expiresIn,
                    'user_id'       => $claims['sub'] ?? '',
                ],
            ]);
        } catch (InvalidTokenException $e) {
            return JsonResponse::unauthorized($e->getMessage());
        } catch (EntityNotFoundException) {
            return JsonResponse::unauthorized('User not found');
        }
    }

    public function logout(Request $request): JsonResponse
    {
        return $this->authMiddleware->process($request, function (Request $req): JsonResponse {
            $auth = $req->getHeader('authorization') ?? '';
            $token = substr($auth, 7);

            try {
                $this->bus->dispatch(new LogoutCommand($token));
            } catch (InvalidTokenException) {
                // Token already expired — still consider logout successful
            }

            return JsonResponse::noContent();
        });
    }

    /** @return array<string, mixed> */
    private function claimsFromToken(string $token): array
    {
        try {
            // Decode without verification just to extract sub — token was just issued
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return [];
            }
            $payload = base64_decode(strtr($parts[1], '-_', '+/'), true);

            if ($payload === false) {
                return [];
            }

            $decoded = json_decode($payload, true);

            return is_array($decoded) ? $decoded : [];
        } catch (\Throwable) {
            return [];
        }
    }
}
