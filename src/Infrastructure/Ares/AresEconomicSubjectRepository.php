<?php

declare(strict_types=1);

namespace Lustrace\Ares2\Infrastructure\Ares;

use Lustrace\Ares2\AresClient;
use Lustrace\Ares2\Domain\Entity\EconomicSubject;
use Lustrace\Ares2\Domain\Repository\EconomicSubjectRepositoryInterface;
use Lustrace\Ares2\Domain\Result\PaginatedResult;
use Lustrace\Ares2\Domain\ValueObject\Ico;
use Lustrace\Ares2\DTO\Pagination;
use Lustrace\Ares2\DTO\SearchRequest;
use Lustrace\Ares2\DTO\Sort;
use Lustrace\Ares2\Enum\DataSource;

final readonly class AresEconomicSubjectRepository implements EconomicSubjectRepositoryInterface
{
    public function __construct(private AresClient $client) {}

    public function getByIco(Ico $ico, DataSource $source = DataSource::CORE): EconomicSubject
    {
        $raw = $this->client->getEconomicSubjectFromSource((string) $ico, $source);

        return EconomicSubject::fromAresData($raw);
    }

    public function search(
        array $criteria,
        Pagination $pagination,
        array $sort = [],
        DataSource $source = DataSource::CORE,
    ): PaginatedResult {
        $response = $this->client->searchEconomicSubjectsInSource(
            $source,
            new SearchRequest($criteria, $pagination, $sort),
        );

        $items = array_map(
            static fn (array $item): EconomicSubject => EconomicSubject::fromAresData($item),
            $response->items,
        );

        return new PaginatedResult(
            items: $items,
            total: $response->total,
            start: $response->start,
            count: $response->count,
        );
    }
}
