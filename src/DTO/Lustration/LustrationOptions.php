<?php

declare(strict_types=1);

namespace Lustrace\Ares2\DTO\Lustration;

use Lustrace\Ares2\DTO\Pagination;
use Lustrace\Ares2\Enum\DataSource;

final readonly class LustrationOptions
{
    /**
     * @param list<DataSource> $sources If empty, all known economic-subject sources are used (except CORE which is controlled by includeCore).
     */
    public function __construct(
        public int $maxTargets = 10,
        public bool $relevantSourcesOnly = true,
        public bool $includeCore = true,
        public ?Pagination $searchPagination = null,
        public array $sources = [],
    ) {
        if ($maxTargets < 1) {
            throw new \InvalidArgumentException('maxTargets must be >= 1.');
        }
    }

    /**
     * @return list<DataSource>
     */
    public function resolvedSources(): array
    {
        if ($this->sources !== []) {
            $sources = $this->sources;
        } else {
            $sources = DataSource::cases();
        }

        if (!$this->includeCore) {
            $sources = array_values(array_filter($sources, static fn (DataSource $s): bool => $s !== DataSource::CORE));
        }

        return $sources;
    }
}
