<?php

declare(strict_types=1);

namespace Lustrace\Ares2\DTO;

/**
 * ARES2 search payload typically uses:
 * - start (int) + pocet (int) as required paging keys
 * - optional razeni
 * - plus any filter criteria supported by the specific endpoint
 */
final readonly class SearchRequest
{
    /**
     * @param array<string, mixed> $criteria
     * @param Sort[] $sort
     */
    public function __construct(
        public array $criteria,
        public Pagination $pagination,
        public array $sort = [],
    ) {}

    public function toArray(): array
    {
        $payload = $this->criteria;

        // paging keys used by ARES docs
        $payload['start'] = $this->pagination->start;
        $payload['pocet'] = $this->pagination->count;

        if ($this->sort !== []) {
            $payload['razeni'] = array_map(static fn (Sort $s) => $s->toArray(), $this->sort);
        }

        return $payload;
    }
}
