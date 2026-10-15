<?php

declare(strict_types=1);

namespace FilebrowserNg\Http;

class Response
{
    /** Set to true in unit tests to throw ResponseSentException instead of exit. */
    public static bool $testMode = false;

    public static function json(mixed $data, int $status = 200): never
    {
        if (self::$testMode) {
            throw new ResponseSentException($status, $data);
        }
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function redirect(string $url, int $status = 302): never
    {
        if (self::$testMode) {
            throw new ResponseSentException($status, ['redirect' => $url]);
        }
        http_response_code($status);
        header('Location: ' . $url);
        exit;
    }

    public static function notFound(): never
    {
        if (self::$testMode) {
            throw new ResponseSentException(404, ['error' => 'Not Found']);
        }
        http_response_code(404);
        header('Content-Type: text/plain');
        echo '404 Not Found';
        exit;
    }

    public static function forbidden(): never
    {
        if (self::$testMode) {
            throw new ResponseSentException(403, ['error' => 'Forbidden']);
        }
        http_response_code(403);
        header('Content-Type: text/plain');
        echo '403 Forbidden';
        exit;
    }

    public static function error(string $message, int $status = 500): never
    {
        if (self::$testMode) {
            throw new ResponseSentException($status, ['error' => $message]);
        }
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $message]);
        exit;
    }

    public static function streamDownload(mixed $stream, string $filename, ?int $size = null): never
    {
        if (self::$testMode) {
            throw new ResponseSentException(200, ['download' => $filename]);
        }
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . str_replace('"', '\\"', $filename) . '"');
        if ($size !== null) {
            header('Content-Length: ' . $size);
        }
        header('Cache-Control: no-cache');
        fpassthru($stream);
        if (is_resource($stream)) {
            fclose($stream);
        }
        exit;
    }
}
