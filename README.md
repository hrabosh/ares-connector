# lustrace/ares2-client

A small, framework-agnostic **ARES2** (CZ) REST API client built on **PSR-18** (HTTP client) + **PSR-17** (message factories).

- Optional **PSR-16 cache** for GET endpoints
- Optional **rate limiter** (simple in-memory fixed window) to avoid bursts
- Clean exceptions for transport vs API errors

## Install

```bash
composer require lustrace/ares2-client
```

You must also install a PSR-18 client + PSR-17 factories, e.g. Symfony HttpClient + Nyholm PSR-7:

```bash
composer require symfony/http-client symfony/psr-http-message-bridge nyholm/psr7
```

## Quick start

```php
use Lustrace\Ares2\AresClient;
use Lustrace\Ares2\DTO\Pagination;
use Lustrace\Ares2\DTO\SearchRequest;
use Lustrace\Ares2\RateLimit\FixedWindowRateLimiter;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;

$http = new Psr18Client(HttpClient::create(['timeout' => 10]));
$psr17 = new Psr17Factory();

$ares = new AresClient(
    httpClient: $http,
    requestFactory: $psr17,
    streamFactory: $psr17,
    cache: null,
    rateLimiter: new FixedWindowRateLimiter(maxRequests: 450, windowSeconds: 60),
);

// GET: detail
$detail = $ares->getEconomicSubject('00006947');

// POST: search
$req = new SearchRequest(
    criteria: ['obchodniJmeno' => 'Ministerstvo financí'],
    pagination: new Pagination(start: 0, count: 10),
);
$res = $ares->searchEconomicSubjects($req);
```

## Endpoints / paths

This package defaults to:

- Base URI: `https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/`
- Detail: `ekonomicke-subjekty/{ico}`
- Search: `ekonomicke-subjekty/vyhledat`

If the official API changes, you can override:

```php
$ares = new AresClient(..., baseUri: 'https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/', searchPath: 'ekonomicke-subjekty/vyhledat');
```

## Errors

- `AresTransportException` — network / timeouts
- `AresApiException` — ARES returned an error payload (HTTP 4xx/5xx)

## License

MIT
