<?php

declare(strict_types=1);

namespace App\Presentation\Http;

final class Request
{
    public readonly string $method;
    public readonly string $uri;
    /** @var array<string, mixed> */
    public readonly array $query;
    /** @var array<string, mixed> */
    public readonly array $body;
    /** @var array<string, string> */
    public readonly array $pathParams;
    /** @var array<string, string> */
    public readonly array $headers;
    public readonly ?string $userId;
    public readonly ?string $userRole;

    /**
     * @param array<string, mixed>  $query
     * @param array<string, mixed>  $body
     * @param array<string, string> $pathParams
     * @param array<string, string> $headers
     */
    private function __construct(
        string $method,
        string $uri,
        array $query = [],
        array $body = [],
        array $pathParams = [],
        array $headers = [],
        ?string $userId = null,
        ?string $userRole = null,
    ) {
        $this->method     = strtoupper($method);
        $this->uri        = $uri;
        $this->query      = $query;
        $this->body       = $body;
        $this->pathParams = $pathParams;
        $this->headers    = array_change_key_case($headers, CASE_LOWER);
        $this->userId     = $userId;
        $this->userRole   = $userRole;
    }

    /** @param array<string, string> $params */
    public function withPathParams(array $params): self
    {
        return new self($this->method, $this->uri, $this->query, $this->body, $params, $this->headers, $this->userId, $this->userRole);
    }

    public function withHeader(string $name, string $value): self
    {
        $headers              = $this->headers;
        $headers[strtolower($name)] = $value;

        return new self($this->method, $this->uri, $this->query, $this->body, $this->pathParams, $headers, $this->userId, $this->userRole);
    }

    public function getHeader(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    public function withAuthClaims(string $userId, string $userRole): self
    {
        return new self($this->method, $this->uri, $this->query, $this->body, $this->pathParams, $this->headers, $userId, $userRole);
    }

    /** @param array<string, mixed> $body */
    public static function create(string $method, string $uri, array $body = []): self
    {
        $parts = parse_url($uri);
        $path  = urldecode($parts['path'] ?? $uri);
        $query = [];

        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        /** @var array<string, mixed> $query */

        return new self($method, $path, $query, $body);
    }

    public static function fromGlobals(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri    = $_SERVER['REQUEST_URI'] ?? '/';
        $body   = [];

        $rawBody = file_get_contents('php://input');
        if ($rawBody !== false && $rawBody !== '') {
            $decoded = json_decode($rawBody, true);
            if (is_array($decoded)) {
                $body = $decoded;
            }
        }

        $headers = [];
        $auth    = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if ($auth !== '') {
            $headers['authorization'] = $auth;
        }

        $request = self::create($method, $uri, $body);

        return $headers !== [] ? new self($request->method, $request->uri, $request->query, $request->body, [], $headers) : $request;
    }
}
