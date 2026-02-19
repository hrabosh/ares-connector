<?php

declare(strict_types=1);

namespace Lustrace\Ares2\DTO\Lustration;

final readonly class SubjectLustrationResult
{
    /**
     * @param array<string, SourceFetchResult> $bySource keyed by DataSource::value
     */
    public function __construct(
        public string $ico,
        public ?string $name,
        public array $bySource,
    ) {}
}
