<?php

declare(strict_types=1);

namespace Tests\Helpers;

use FilebrowserNg\Http\Request;

/**
 * A Request subclass for unit tests.
 * Bypasses superglobals and php://input so controllers can be tested without an HTTP server.
 */
final class TestRequest extends Request
{
    private string  $_method;
    private string  $_path;
    private array   $_query;
    private array   $_body;
    private array   $_files;
    private ?array  $_jsonBody;

    public function __construct(
        string $method   = 'GET',
        string $path     = '/',
        array  $query    = [],
        array  $body     = [],
        array  $files    = [],
        ?array $jsonBody = null,
    ) {
        // Do NOT call parent::__construct() – it reads superglobals.
        $this->_method   = strtoupper($method);
        $this->_path     = $path;
        $this->_query    = $query;
        $this->_body     = $body;
        $this->_files    = $files;
        $this->_jsonBody = $jsonBody;
    }

    public function getMethod(): string  { return $this->_method; }
    public function getPath(): string    { return $this->_path; }

    public function getQuery(string $key, mixed $default = null): mixed
    {
        return $this->_query[$key] ?? $default;
    }

    public function getBody(string $key, mixed $default = null): mixed
    {
        return $this->_body[$key] ?? $default;
    }

    public function getFiles(): array       { return $this->_files; }
    public function getFile(string $key): ?array { return $this->_files[$key] ?? null; }
    public function isPost(): bool          { return $this->_method === 'POST'; }
    public function isGet(): bool           { return $this->_method === 'GET'; }
    public function isAjax(): bool          { return false; }
    public function getHeader(string $name, string $default = ''): string { return $default; }

    public function jsonBody(): array
    {
        return $this->_jsonBody ?? [];
    }
}
