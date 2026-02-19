<?php

declare(strict_types=1);

namespace Lustrace\Ares2\Application;

use Lustrace\Ares2\Domain\Entity\EconomicSubject;
use Lustrace\Ares2\Domain\Repository\EconomicSubjectRepositoryInterface;
use Lustrace\Ares2\Domain\Result\PaginatedResult;
use Lustrace\Ares2\Domain\ValueObject\Ico;
use Lustrace\Ares2\DTO\Pagination;
use Lustrace\Ares2\DTO\Sort;
use Lustrace\Ares2\Enum\DataSource;

final readonly class EconomicSubjectService
{
    public function __construct(private EconomicSubjectRepositoryInterface $repository) {}

    public function getByIco(string $ico, DataSource $source = DataSource::CORE): EconomicSubject
    {
        return $this->repository->getByIco(new Ico($ico), $source);
    }

    /**
     * @param array<string,mixed> $criteria
     * @param list<Sort> $sort
     */
    public function search(
        array $criteria,
        Pagination $pagination,
        array $sort = [],
        DataSource $source = DataSource::CORE,
    ): PaginatedResult {
        return $this->repository->search($criteria, $pagination, $sort, $source);
    }
}
