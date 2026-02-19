<?php

declare(strict_types=1);

namespace Lustrace\Ares2\Domain\ValueObject;

use InvalidArgumentException;

final readonly class Ico
{
    public function __construct(public string $value)
    {
        if (!preg_match('/^\d{8}$/', $value)) {
            throw new InvalidArgumentException(sprintf('ICO must contain exactly 8 digits, got "%s".', $value));
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
