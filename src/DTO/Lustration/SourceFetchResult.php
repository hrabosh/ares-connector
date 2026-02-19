<?php

declare(strict_types=1);

namespace Lustrace\Ares2\DTO\Lustration;

use Lustrace\Ares2\Enum\DataSource;

final readonly class SourceFetchResult
{
    /**
     * @param array<string,mixed>|null $data
     * @param array<string,mixed>|null $error
     */
    public function __construct(
        public DataSource $source,
        public SourceFetchStatus $status,
        public ?array $data = null,
        public ?array $error = null,
        public ?int $httpStatus = null,
    ) {}
}
