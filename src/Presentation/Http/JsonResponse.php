<?php

declare(strict_types=1);

namespace App\Presentation\Http;

final class JsonResponse
{
    public readonly int $statusCode;
    /** @var array<string, mixed> */
    public readonly array $data;

    /** @param array<string, mixed> $data */
    private function __construct(int $statusCode, array $data)
    {
        $this->statusCode = $statusCode;
        $this->data = $data;
    }

    /** @param array<string, mixed> $data */
    public static function ok(array $data): self
    {
        return new self(200, $data);
    }

    /** @param array<string, mixed> $data */
    public static function created(array $data): self
    {
        return new self(201, $data);
    }

    public static function notFound(string $message): self
    {
        return new self(404, ['error' => ['code' => 'NOT_FOUND', 'message' => $message]]);
    }

    public static function methodNotAllowed(): self
    {
        return new self(405, ['error' => ['code' => 'METHOD_NOT_ALLOWED', 'message' => 'Method not allowed']]);
    }

    /** @param array<string, string> $errors */
    public static function unprocessableEntity(array $errors): self
    {
        return new self(422, ['error' => ['code' => 'VALIDATION_ERROR', 'fields' => $errors]]);
    }

    public static function unauthorized(string $message = 'Unauthorized'): self
    {
        return new self(401, ['error' => ['code' => 'UNAUTHORIZED', 'message' => $message]]);
    }

    public static function forbidden(string $message = 'Forbidden'): self
    {
        return new self(403, ['error' => ['code' => 'FORBIDDEN', 'message' => $message]]);
    }

    public static function conflict(string $message): self
    {
        return new self(409, ['error' => ['code' => 'CONFLICT', 'message' => $message]]);
    }

    public static function internalError(string $message = 'Internal server error'): self
    {
        return new self(500, ['error' => ['code' => 'INTERNAL_ERROR', 'message' => $message]]);
    }

    public function send(): void
    {
        http_response_code($this->statusCode);
        header('Content-Type: application/json');
        echo json_encode($this->data, JSON_THROW_ON_ERROR);
    }
}
