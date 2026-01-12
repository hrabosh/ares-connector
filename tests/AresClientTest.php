<?php

declare(strict_types=1);

namespace Lustrace\Ares2\Tests;

use Lustrace\Ares2\AresClient;
use Lustrace\Ares2\DTO\Pagination;
use Lustrace\Ares2\DTO\SearchRequest;
use Lustrace\Ares2\Exception\AresApiException;
use Lustrace\Ares2\Tests\Support\MockHttpClient;
use Lustrace\Ares2\Tests\Support\SimpleRequestFactory;
use Lustrace\Ares2\Tests\Support\SimpleResponse;
use Lustrace\Ares2\Tests\Support\SimpleStream;
use Lustrace\Ares2\Tests\Support\SimpleStreamFactory;
use PHPUnit\Framework\TestCase;

final class AresClientTest extends TestCase
{
    public function testGetEconomicSubjectParsesJson(): void
    {
        $http = new MockHttpClient();
        $http->enqueue(new SimpleResponse(
            200,
            ['Content-Type' => ['application/json']],
            new SimpleStream(json_encode(['ico' => '00006947', 'obchodniJmeno' => 'Test'], JSON_UNESCAPED_UNICODE)),
        ));

        $client = new AresClient(
            httpClient: $http,
            requestFactory: new SimpleRequestFactory(),
            streamFactory: new SimpleStreamFactory(),
            baseUri: 'https://example.test/rest/',
        );

        $res = $client->getEconomicSubject('00006947');

        self::assertSame('00006947', $res['ico']);
        self::assertCount(1, $http->requests);
        self::assertSame('https://example.test/rest/ekonomicke-subjekty/00006947', (string) $http->requests[0]->getRequestTarget());
    }

    public function testSearchEconomicSubjectsReturnsItems(): void
    {
        $http = new MockHttpClient();
        $http->enqueue(new SimpleResponse(
            200,
            ['Content-Type' => ['application/json']],
            new SimpleStream(json_encode([
                'pocetCelkem' => 2,
                'ekonomickeSubjekty' => [
                    ['ico' => '1'],
                    ['ico' => '2'],
                ],
            ], JSON_UNESCAPED_UNICODE)),
        ));

        $client = new AresClient(
            httpClient: $http,
            requestFactory: new SimpleRequestFactory(),
            streamFactory: new SimpleStreamFactory(),
            baseUri: 'https://example.test/rest/',
            searchPath: 'ekonomicke-subjekty/vyhledat',
        );

        $req = new SearchRequest(criteria: ['obchodniJmeno' => 'x'], pagination: new Pagination(0, 10));
        $res = $client->searchEconomicSubjects($req);

        self::assertSame(2, $res->total);
        self::assertCount(2, $res->items);
    }

    public function testApiErrorThrowsAresApiException(): void
    {
        $http = new MockHttpClient();
        $http->enqueue(new SimpleResponse(
            404,
            ['Content-Type' => ['application/json']],
            new SimpleStream(json_encode(['kod' => 'NOT_FOUND', 'description' => 'Not found'], JSON_UNESCAPED_UNICODE)),
        ));

        $client = new AresClient(
            httpClient: $http,
            requestFactory: new SimpleRequestFactory(),
            streamFactory: new SimpleStreamFactory(),
            baseUri: 'https://example.test/rest/',
        );

        $this->expectException(AresApiException::class);
        $client->getEconomicSubject('00000000');
    }
}
