<?php

declare(strict_types=1);

namespace Lustrace\Ares2\Domain\Result;

final readonly class PaginatedResult
{
    /**
     * @param list<mixed> $items
     */
    public function __construct(
        public array $items,
        public ?int $total,
        public int $start,
        public int $count,
    ) {}
}
