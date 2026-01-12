<?php

declare(strict_types=1);

namespace Lustrace\Ares2;

use Lustrace\Ares2\DTO\SearchRequest;
use Lustrace\Ares2\DTO\SearchResponse;
use Lustrace\Ares2\Enum\DataSource;
use Lustrace\Ares2\Exception\AresApiException;
use Lustrace\Ares2\Exception\AresTransportException;
use Lustrace\Ares2\RateLimit\RateLimiterInterface;
use Lustrace\Ares2\RateLimit\RateLimitExceededException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface as HttpClientInterface;
use Psr\Http\Factory\RequestFactoryInterface;
use Psr\Http\Factory\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\SimpleCache\CacheInterface;

final class AresClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly ?CacheInterface $cache = null,
        private readonly ?RateLimiterInterface $rateLimiter = null,
        ?LoggerInterface $logger = null,
        private readonly string $baseUri = 'https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/',
        private readonly string $searchPath = 'ekonomicke-subjekty/vyhledat',
        private readonly int $defaultCacheTtlSeconds = 300,
        private readonly string $rateLimitKey = 'ares2',
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    private LoggerInterface $logger;

    /**
     * Fetch subject detail from the default (CORE) endpoint.
     *
     * @return array<string, mixed>
     */
    public function getEconomicSubject(string $ico): array
    {
        return $this->getEconomicSubjectFromSource($ico, DataSource::CORE);
    }

    /**
     * Fetch subject detail from a specific service (dataset).
     *
     * @return array<string, mixed>
     */
    public function getEconomicSubjectFromSource(string $ico, DataSource $source): array
    {
        $path = $source->detailPath($ico);

        $cacheKey = sprintf('ares2:%s:%s', $source->value, trim($ico));
        if ($this->cache !== null) {
            $cached = $this->cache->get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $data = $this->requestJson('GET', $path, null);

        if ($this->cache !== null) {
            $this->cache->set($cacheKey, $data, $this->defaultCacheTtlSeconds);
        }

        return $data;
    }

    public function searchEconomicSubjects(SearchRequest $request): SearchResponse
    {
        return $this->searchEconomicSubjectsInSource(DataSource::CORE, $request);
    }

    public function searchEconomicSubjectsInSource(DataSource $source, SearchRequest $request): SearchResponse
    {
        $path = $source === DataSource::CORE ? $this->searchPath : $source->searchPath();

        $raw = $this->requestJson('POST', $path, $request->toArray());

        $items = [];
        if (isset($raw['ekonomickeSubjekty']) && is_array($raw['ekonomickeSubjekty'])) {
            $items = $raw['ekonomickeSubjekty'];
        } elseif (isset($raw['data']) && is_array($raw['data'])) {
            $items = $raw['data'];
        }

        $total = null;
        foreach (['pocetCelkem', 'pocetCelkemZaznamu', 'total'] as $k) {
            if (isset($raw[$k]) && is_int($raw[$k])) {
                $total = $raw[$k];
                break;
            }
        }

        // Use request pagination as source of truth.
        return new SearchResponse(
            items: $items,
            total: $total,
            start: $request->pagination->start,
            count: $request->pagination->count,
            raw: $raw,
        );
    }

    /**
     * @param array<string, mixed>|null $json
     * @return array<string, mixed>
     */
    private function requestJson(string $method, string $path, ?array $json): array
    {
        $url = rtrim($this->baseUri, '/') . '/' . ltrim($path, '/');

        if ($this->rateLimiter !== null) {
            $this->rateLimiter->consume($this->rateLimitKey);
        }

        $req = $this->requestFactory->createRequest($method, $url)
            ->withHeader('Accept', 'application/json');

        if ($json !== null) {
            $body = $this->streamFactory->createStream(json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $req = $req->withHeader('Content-Type', 'application/json')->withBody($body);
        }

        $t0 = microtime(true);

        try {
            $res = $this->httpClient->sendRequest($req);
        } catch (RateLimitExceededException $e) {
            $this->logger->warning('ARES2 rate limit exceeded', ['retry_after' => $e->retryAfterSeconds]);
            throw $e;
        } catch (ClientExceptionInterface $e) {
            throw new AresTransportException('HTTP client error: ' . $e->getMessage(), 0, $e);
        } catch (\Throwable $e) {
            throw new AresTransportException('Transport error: ' . $e->getMessage(), 0, $e);
        } finally {
            $dt = (int) round((microtime(true) - $t0) * 1000);
            $this->logger->debug('ARES2 request completed', ['method' => $method, 'path' => $path, 'ms' => $dt]);
        }

        $status = $res->getStatusCode();
        $bodyStr = (string) $res->getBody();

        $decoded = null;
        if ($bodyStr !== '') {
            $decoded = json_decode($bodyStr, true);
        }

        if ($status >= 400) {
            $errorCode = is_array($decoded) && isset($decoded['kod']) ? (string) $decoded['kod'] : null;
            $subCode = is_array($decoded) && isset($decoded['subKod']) ? (string) $decoded['subKod'] : null;
            $desc = is_array($decoded) && isset($decoded['description']) ? (string) $decoded['description'] : null;

            $msg = $desc ?? sprintf('ARES2 API error (HTTP %d)', $status);

            throw new AresApiException(
                message: $msg,
                httpStatus: $status,
                errorCode: $errorCode,
                subCode: $subCode,
                errorDescription: $desc,
            );
        }

        if (!is_array($decoded)) {
            // ARES should return JSON; if not, fail fast.
            throw new AresTransportException('Invalid JSON response from ARES2.');
        }

        /** @var array<string,mixed> $decoded */
        return $decoded;
    }
}
