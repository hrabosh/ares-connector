<?php

declare(strict_types=1);

namespace Lustrace\Ares2\Tests;

use Lustrace\Ares2\AresClient;
use Lustrace\Ares2\Application\AresLustrationService;
use Lustrace\Ares2\DTO\Lustration\LustrationOptions;
use Lustrace\Ares2\DTO\Lustration\LustrationQuery;
use Lustrace\Ares2\DTO\Pagination;
use Lustrace\Ares2\Infrastructure\Http\RetryPolicy;
use Lustrace\Ares2\Tests\Support\FakeHttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class AresLustrationServiceTest extends TestCase
{
    public function testFetchesOnlyRelevantSourcesWhenEnabled(): void
    {
        $psr17 = new Psr17Factory();

        $routes = [
            'POST /rest/ekonomicke-subjekty/vyhledat' => new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'pocetCelkem' => 1,
                    'ekonomickeSubjekty' => [
                        ['ico' => '00000001', 'obchodniJmeno' => 'Foo s.r.o.'],
                    ],
                ])
            ),
            'GET /rest/ekonomicke-subjekty/00000001' => new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'ico' => '00000001',
                    'obchodniJmeno' => 'Foo s.r.o.',
                    'seznamRegistraci' => [
                        'stavZdrojeRes' => 'A',
                        'stavZdrojeVr' => 'A',
                        'stavZdrojeRzp' => null,
                    ],
                ])
            ),
            'GET /rest/ekonomicke-subjekty-res/00000001' => new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode(['ico' => '00000001', 'resKey' => 'resVal'])
            ),
            'GET /rest/ekonomicke-subjekty-vr/00000001' => new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode(['ico' => '00000001', 'vrKey' => 'vrVal'])
            ),
        ];

        $http = new FakeHttpClient(routes: $routes);

        $client = new AresClient(
            httpClient: $http,
            requestFactory: $psr17,
            streamFactory: $psr17,
            baseUri: 'https://example.test/rest/',
            retryPolicy: new RetryPolicy(maxRetries: 0),
        );

        $svc = new AresLustrationService($client);

        $res = $svc->run(
            LustrationQuery::forCompanyName('Foo'),
            new LustrationOptions(
                maxTargets: 5,
                relevantSourcesOnly: true,
                searchPagination: new Pagination(0, 5),
            )
        );

        self::assertCount(1, $res->subjects);
        $subject = $res->subjects[0];

        self::assertSame('00000001', $subject->ico);
        self::assertSame('Foo s.r.o.', $subject->name);

        self::assertArrayHasKey('ekonomicke-subjekty', $subject->bySource);
        self::assertArrayHasKey('ekonomicke-subjekty-res', $subject->bySource);
        self::assertArrayHasKey('ekonomicke-subjekty-vr', $subject->bySource);

        // search + core + res + vr
        self::assertCount(4, $http->requests);
    }
}
