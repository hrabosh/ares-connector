<?php

declare(strict_types=1);

namespace Lustrace\Ares2\Tests\Support;

use Lustrace\Ares2\Infrastructure\Http\SleeperInterface;

final class FakeSleeper implements SleeperInterface
{
    /** @var list<int> */
    public array $slept = [];

    public function sleepMillis(int $ms): void
    {
        $this->slept[] = $ms;
    }
}
