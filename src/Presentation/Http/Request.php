<?php

declare(strict_types=1);

namespace App\Presentation\Http;

final class Request
{
    public readonly string $method;
    public readonly string $uri;
    public readonly array $query;
    public readonly array $body;

    private function __construct(
        string $method,
        string $uri,
        array $query = [],
        array $body = [],
    ) {
        $this->method = strtoupper($method);
        $this->uri = $uri;
        $this->query = $query;
        $this->body = $body;
    }

    public static function create(string $method, string $uri, array $body = []): self
    {
        $parts = parse_url($uri);
        $path = $parts['path'] ?? $uri;
        $query = [];

        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        return new self($method, $path, $query, $body);
    }

    public static function fromGlobals(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $body = [];

        $rawBody = file_get_contents('php://input');
        if ($rawBody !== false && $rawBody !== '') {
            $decoded = json_decode($rawBody, true);
            if (is_array($decoded)) {
                $body = $decoded;
            }
        }

        return self::create($method, $uri, $body);
    }
}
