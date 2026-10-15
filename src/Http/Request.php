<?php

declare(strict_types=1);

namespace FilebrowserNg\Http;

class Request
{
    private string $method;
    private string $path;
    private array $query;
    private array $body;
    private array $files;
    private array $headers;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $parts = explode('?', $uri, 2);
        $this->path = rtrim($parts[0], '/') ?: '/';
        $query = [];
        parse_str($parts[1] ?? '', $query);
        $this->query = $query;
        $this->body = $_POST;
        $this->files = $_FILES;
        $this->headers = $this->parseHeaders();
    }

    private function parseHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                $headers[strtolower($name)] = $value;
            }
        }
        return $headers;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getQuery(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function getBody(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    public function getFiles(): array
    {
        return $this->files;
    }

    public function getFile(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function isGet(): bool
    {
        return $this->method === 'GET';
    }

    public function isAjax(): bool
    {
        return ($this->headers['x-requested-with'] ?? '') === 'XMLHttpRequest';
    }

    public function getHeader(string $name, string $default = ''): string
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    public function jsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if (empty($raw)) {
            return [];
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
}
