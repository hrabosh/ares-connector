<?php

declare(strict_types=1);

namespace Lustrace\Ares2\Tests\Infrastructure\Ares;

use Lustrace\Ares2\AresClient;
use Lustrace\Ares2\Domain\ValueObject\Ico;
use Lustrace\Ares2\DTO\Pagination;
use Lustrace\Ares2\Enum\DataSource;
use Lustrace\Ares2\Infrastructure\Ares\AresEconomicSubjectRepository;
use Lustrace\Ares2\Tests\Support\MockHttpClient;
use Lustrace\Ares2\Tests\Support\SimpleRequestFactory;
use Lustrace\Ares2\Tests\Support\SimpleResponse;
use Lustrace\Ares2\Tests\Support\SimpleStream;
use Lustrace\Ares2\Tests\Support\SimpleStreamFactory;
use PHPUnit\Framework\TestCase;

final class AresEconomicSubjectRepositoryTest extends TestCase
{
    public function testGetByIcoMapsRawDataToDomainEntity(): void
    {
        $http = new MockHttpClient();
        $http->enqueue(new SimpleResponse(
            200,
            ['Content-Type' => ['application/json']],
            new SimpleStream(json_encode(['ico' => '00006947', 'obchodniJmeno' => 'MF CR'], JSON_UNESCAPED_UNICODE)),
        ));

        $client = new AresClient($http, new SimpleRequestFactory(), new SimpleStreamFactory(), baseUri: 'https://example.test/rest/');
        $repository = new AresEconomicSubjectRepository($client);

        $subject = $repository->getByIco(new Ico('00006947'));

        self::assertSame('00006947', (string) $subject->ico);
        self::assertSame('MF CR', $subject->name);
    }

    public function testSearchReturnsPaginatedDomainResult(): void
    {
        $http = new MockHttpClient();
        $http->enqueue(new SimpleResponse(
            200,
            ['Content-Type' => ['application/json']],
            new SimpleStream(json_encode([
                'pocetCelkem' => 2,
                'ekonomickeSubjekty' => [
                    ['ico' => '00000001', 'obchodniJmeno' => 'A'],
                    ['ico' => '00000002', 'obchodniJmeno' => 'B'],
                ],
            ], JSON_UNESCAPED_UNICODE)),
        ));

        $client = new AresClient($http, new SimpleRequestFactory(), new SimpleStreamFactory(), baseUri: 'https://example.test/rest/');
        $repository = new AresEconomicSubjectRepository($client);

        $result = $repository->search(['obchodniJmeno' => 'Test'], new Pagination(0, 10), source: DataSource::CORE);

        self::assertSame(2, $result->total);
        self::assertCount(2, $result->items);
        self::assertSame('00000001', (string) $result->items[0]->ico);
    }
}
