<?php

declare(strict_types=1);

namespace Lustrace\Ares2\DTO;

final readonly class Pagination
{
    public function __construct(
        public int $start,
        public int $count,
    ) {
        if ($start < 0) {
            throw new \InvalidArgumentException('Pagination start must be >= 0.');
        }
        if ($count < 1) {
            throw new \InvalidArgumentException('Pagination count must be >= 1.');
        }
    }
}
