<?php

declare(strict_types=1);

namespace Lustrace\Ares2\DTO\Lustration;

final readonly class LustrationRunResult
{
    /**
     * @param list<SubjectLustrationResult> $subjects
     * @param array<string,mixed>|null $searchRaw
     */
    public function __construct(
        public LustrationQuery $query,
        public array $subjects,
        public ?array $searchRaw = null,
    ) {}
}
