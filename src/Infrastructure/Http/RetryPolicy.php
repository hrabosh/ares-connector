<?php

declare(strict_types=1);

namespace Lustrace\Ares2\Infrastructure\Http;

use Lustrace\Ares2\Exception\AresApiException;

final readonly class RetryPolicy
{
    /**
     * @param list<int> $retryHttpStatusCodes
     */
    public function __construct(
        public int $maxRetries = 2,
        public int $baseDelayMs = 200,
        public int $maxDelayMs = 2000,
        public float $jitter = 0.2,
        public array $retryHttpStatusCodes = [429, 500, 502, 503, 504],
    ) {
        if ($maxRetries < 0) {
            throw new \InvalidArgumentException('maxRetries must be >= 0.');
        }
        if ($baseDelayMs < 0 || $maxDelayMs < 0) {
            throw new \InvalidArgumentException('Delay must be >= 0.');
        }
        if ($jitter < 0.0 || $jitter > 1.0) {
            throw new \InvalidArgumentException('jitter must be in <0,1>.');
        }
    }

    public function shouldRetryTransport(int $attempt): bool
    {
        return $attempt < $this->maxRetries;
    }

    public function shouldRetryApi(AresApiException $e, int $attempt): bool
    {
        if ($attempt >= $this->maxRetries) {
            return false;
        }
        return in_array($e->httpStatus, $this->retryHttpStatusCodes, true);
    }

    public function computeDelayMs(int $attempt, ?int $retryAfterSeconds = null): int
    {
        if ($attempt <= 0) {
            $attempt = 1;
        }

        if ($retryAfterSeconds !== null && $retryAfterSeconds > 0) {
            $ms = $retryAfterSeconds * 1000;
            return min($this->maxDelayMs, $ms);
        }

        // exponential backoff: base * 2^(attempt-1)
        $delay = (int) round($this->baseDelayMs * (2 ** ($attempt - 1)));
        $delay = min($this->maxDelayMs, $delay);

        if ($delay <= 0 || $this->jitter <= 0) {
            return $delay;
        }

        $j = (int) round($delay * $this->jitter);
        $rand = random_int(-$j, $j);
        $delay = max(0, $delay + $rand);

        return $delay;
    }
}
