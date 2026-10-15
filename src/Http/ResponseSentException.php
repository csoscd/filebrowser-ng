<?php

declare(strict_types=1);

namespace FilebrowserNg\Http;

/**
 * Thrown by Response in test mode instead of calling exit.
 * Inspect $statusCode and $data to assert on the response.
 */
final class ResponseSentException extends \RuntimeException
{
    public function __construct(
        public readonly int   $statusCode,
        public readonly mixed $data,
    ) {
        parent::__construct('Response sent with status ' . $statusCode);
    }
}
