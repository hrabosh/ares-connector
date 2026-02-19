<?php

declare(strict_types=1);

namespace Lustrace\Ares2\Tests;

use Lustrace\Ares2\Infrastructure\Http\EndpointPool;
use PHPUnit\Framework\TestCase;

final class EndpointPoolTest extends TestCase
{
    public function testRoundRobinPick(): void
    {
        $pool = new EndpointPool(['https://a.test/', 'https://b.test/', 'https://c.test/'], cooldownSeconds: 1);

        self::assertSame('https://a.test/', $pool->pick());
        self::assertSame('https://b.test/', $pool->pick());
        self::assertSame('https://c.test/', $pool->pick());
        self::assertSame('https://a.test/', $pool->pick());
    }
}
