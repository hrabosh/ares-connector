<?php

declare(strict_types=1);

namespace Lustrace\Ares2\Infrastructure\Http;

/**
 * Very small endpoint pool for client-side load balancing / failover.
 *
 * - Round-robin selection across configured base URIs
 * - Optional "cooldown" after transport or 5xx failures
 *
 * This is useful if the upstream provides multiple hosts, or if you run
 * your own forward proxy layer with multiple instances.
 */
final class EndpointPool
{
    /** @var list<string> */
    private array $endpoints;

    private int $idx = 0;

    /** @var array<string,int> endpoint => unix timestamp until which it is considered down */
    private array $downUntil = [];

    public function __construct(
        array $endpoints,
        private readonly int $cooldownSeconds = 10,
    ) {
        $normalized = [];
        foreach ($endpoints as $e) {
            if (!is_string($e)) {
                continue;
            }
            $e = rtrim(trim($e), '/') . '/';
            if ($e !== '/' && $e !== '' && !in_array($e, $normalized, true)) {
                $normalized[] = $e;
            }
        }

        if ($normalized === []) {
            throw new \InvalidArgumentException('EndpointPool requires at least one base URI.');
        }

        $this->endpoints = $normalized;
    }

    public function markFailure(string $endpoint): void
    {
        $this->downUntil[$endpoint] = time() + max(1, $this->cooldownSeconds);
    }

    public function pick(): string
    {
        $n = count($this->endpoints);
        $now = time();

        for ($i = 0; $i < $n; $i++) {
            $candidate = $this->endpoints[$this->idx % $n];
            $this->idx++;

            $until = $this->downUntil[$candidate] ?? 0;
            if ($until <= $now) {
                return $candidate;
            }
        }

        // if all are down, just return next in RR (fail-fast will happen upstream)
        return $this->endpoints[$this->idx++ % $n];
    }
}
