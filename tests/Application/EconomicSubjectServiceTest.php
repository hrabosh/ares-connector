<?php

declare(strict_types=1);

namespace Lustrace\Ares2\Tests\Application;

use Lustrace\Ares2\Application\EconomicSubjectService;
use Lustrace\Ares2\Domain\Entity\EconomicSubject;
use Lustrace\Ares2\Domain\Repository\EconomicSubjectRepositoryInterface;
use Lustrace\Ares2\Domain\Result\PaginatedResult;
use Lustrace\Ares2\Domain\ValueObject\Ico;
use Lustrace\Ares2\DTO\Pagination;
use Lustrace\Ares2\Enum\DataSource;
use PHPUnit\Framework\TestCase;

final class EconomicSubjectServiceTest extends TestCase
{
    public function testGetByIcoDelegatesToRepository(): void
    {
        $repository = new class implements EconomicSubjectRepositoryInterface {
            public function getByIco(Ico $ico, DataSource $source = DataSource::CORE): EconomicSubject
            {
                TestCase::assertSame('00006947', (string) $ico);
                return new EconomicSubject($ico, 'MF');
            }

            public function search(array $criteria, Pagination $pagination, array $sort = [], DataSource $source = DataSource::CORE): PaginatedResult
            {
                return new PaginatedResult([], 0, $pagination->start, $pagination->count);
            }
        };

        $service = new EconomicSubjectService($repository);

        $result = $service->getByIco('00006947');

        self::assertSame('MF', $result->name);
    }
}
