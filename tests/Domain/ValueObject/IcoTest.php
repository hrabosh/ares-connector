<?php

declare(strict_types=1);

namespace Lustrace\Ares2\Tests\Domain\ValueObject;

use InvalidArgumentException;
use Lustrace\Ares2\Domain\ValueObject\Ico;
use PHPUnit\Framework\TestCase;

final class IcoTest extends TestCase
{
    public function testValidIcoIsAccepted(): void
    {
        $ico = new Ico('12345678');

        self::assertSame('12345678', (string) $ico);
    }

    public function testInvalidIcoThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Ico('1234');
    }
}
