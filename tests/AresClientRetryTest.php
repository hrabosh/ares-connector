<?php

declare(strict_types=1);

namespace Lustrace\Ares2\Tests;

use Lustrace\Ares2\AresClient;
use Lustrace\Ares2\Exception\AresApiException;
use Lustrace\Ares2\Infrastructure\Http\RetryPolicy;
use Lustrace\Ares2\Tests\Support\FakeHttpClient;
use Lustrace\Ares2\Tests\Support\FakeSleeper;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class AresClientRetryTest extends TestCase
{
    public function testRetriesOnServerError(): void
    {
        $http = new FakeHttpClient(queue: [
            new Response(500, ['Content-Type' => 'application/json'], json_encode(['kod' => 'OBECNA_CHYBA', 'popis' => 'boom'])),
            new Response(200, ['Content-Type' => 'application/json'], json_encode(['ico' => '00000001', 'obchodniJmeno' => 'X'])),
        ]);

        $psr17 = new Psr17Factory();
        $sleeper = new FakeSleeper();

        $client = new AresClient(
            httpClient: $http,
            requestFactory: $psr17,
            streamFactory: $psr17,
            baseUri: 'https://example.test/rest/',
            retryPolicy: new RetryPolicy(maxRetries: 2, baseDelayMs: 0, maxDelayMs: 0),
            sleeper: $sleeper,
        );

        $data = $client->getEconomicSubject('00000001');

        self::assertSame('00000001', $data['ico']);
        self::assertCount(2, $http->requests);
        self::assertNotEmpty($sleeper->slept);
    }

    public function testDoesNotRetryOnNotFound(): void
    {
        $http = new FakeHttpClient(queue: [
            new Response(404, ['Content-Type' => 'application/json'], json_encode(['kod' => 'NENALEZENO', 'popis' => 'not found'])),
        ]);

        $psr17 = new Psr17Factory();

        $client = new AresClient(
            httpClient: $http,
            requestFactory: $psr17,
            streamFactory: $psr17,
            baseUri: 'https://example.test/rest/',
            retryPolicy: new RetryPolicy(maxRetries: 2, baseDelayMs: 0, maxDelayMs: 0),
        );

        $this->expectException(AresApiException::class);
        $client->getEconomicSubject('00000001');

        self::assertCount(1, $http->requests);
    }
}
