<?php

declare(strict_types=1);

namespace App\Presentation\Http;

final class JsonResponse
{
    public readonly int $statusCode;
    public readonly array $data;

    private function __construct(int $statusCode, array $data)
    {
        $this->statusCode = $statusCode;
        $this->data = $data;
    }

    public static function ok(array $data): self
    {
        return new self(200, $data);
    }

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

    public function send(): void
    {
        http_response_code($this->statusCode);
        header('Content-Type: application/json');
        echo json_encode($this->data, JSON_THROW_ON_ERROR);
    }
}
