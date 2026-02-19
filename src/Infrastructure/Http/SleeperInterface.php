<?php

declare(strict_types=1);

namespace Lustrace\Ares2\Infrastructure\Http;

interface SleeperInterface
{
    public function sleepMillis(int $ms): void;
}

final class NativeSleeper implements SleeperInterface
{
    public function sleepMillis(int $ms): void
    {
        if ($ms <= 0) {
            return;
        }
        usleep($ms * 1000);
    }
}
