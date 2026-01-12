<?php

declare(strict_types=1);

namespace Lustrace\Ares2\DTO;

final readonly class SearchResponse
{
    /**
     * @param array<int, array<string, mixed>> $items
     */
    public function __construct(
        public array $items,
        public ?int $total,
        public int $start,
        public int $count,
        public array $raw,
    ) {}
}
