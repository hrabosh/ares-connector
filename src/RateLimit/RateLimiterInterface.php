<?php

declare(strict_types=1);

namespace Lustrace\Ares2\RateLimit;

interface RateLimiterInterface
{
    /**
     * Consume one token for the given key (e.g. per-tenant key in SaaS).
     * Implementations should throw RateLimitExceededException when blocked.
     */
    public function consume(string $key): void;
}
