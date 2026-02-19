<?php

declare(strict_types=1);

namespace Lustrace\Ares2\Domain\Repository;

use Lustrace\Ares2\Domain\Entity\EconomicSubject;
use Lustrace\Ares2\Domain\Result\PaginatedResult;
use Lustrace\Ares2\Domain\ValueObject\Ico;
use Lustrace\Ares2\DTO\Pagination;
use Lustrace\Ares2\DTO\Sort;
use Lustrace\Ares2\Enum\DataSource;

interface EconomicSubjectRepositoryInterface
{
    public function getByIco(Ico $ico, DataSource $source = DataSource::CORE): EconomicSubject;

    /**
     * @param array<string,mixed> $criteria
     * @param list<Sort> $sort
     */
    public function search(
        array $criteria,
        Pagination $pagination,
        array $sort = [],
        DataSource $source = DataSource::CORE,
    ): PaginatedResult;
}
