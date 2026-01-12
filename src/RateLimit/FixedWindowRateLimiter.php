<?php

declare(strict_types=1);

namespace Lustrace\Ares2\RateLimit;

/**
 * Very small, in-memory limiter. Suitable as a safeguard in single-process environments.
 * For multi-node / multi-process workloads use Symfony RateLimiter or Redis-based solution.
 */
final class FixedWindowRateLimiter implements RateLimiterInterface
{
    /** @var array<string, array{windowStart:int, used:int}> */
    private array $state = [];

    public function __construct(
        private readonly int $maxRequests,
        private readonly int $windowSeconds,
    ) {
        if ($maxRequests < 1) {
            throw new \InvalidArgumentException('maxRequests must be >= 1.');
        }
        if ($windowSeconds < 1) {
            throw new \InvalidArgumentException('windowSeconds must be >= 1.');
        }
    }

    public function consume(string $key): void
    {
        $now = time();

        $bucket = $this->state[$key] ?? ['windowStart' => $now, 'used' => 0];

        // reset window
        if ($now - $bucket['windowStart'] >= $this->windowSeconds) {
            $bucket = ['windowStart' => $now, 'used' => 0];
        }

        if ($bucket['used'] >= $this->maxRequests) {
            $retry = ($bucket['windowStart'] + $this->windowSeconds) - $now;
            if ($retry < 1) {
                $retry = 1;
            }
            throw new RateLimitExceededException($retry);
        }

        $bucket['used']++;
        $this->state[$key] = $bucket;
    }
}
