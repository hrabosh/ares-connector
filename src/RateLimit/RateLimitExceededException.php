<?php

declare(strict_types=1);

namespace Lustrace\Ares2\RateLimit;

final class RateLimitExceededException extends \RuntimeException
{
    public function __construct(
        public readonly int $retryAfterSeconds,
        string $message = 'Rate limit exceeded.',
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
