# lustrace/ares2-client

A professional, framework-agnostic **ARES2** (CZ) REST API client built on **PSR-18** (HTTP client) + **PSR-17** (message factories).

- Optional **PSR-16 cache** for GET endpoints
- Optional **rate limiter** (simple in-memory fixed window) to avoid bursts
- Clean exceptions for transport vs API errors
- **DDD-friendly abstractions** usable in plain PHP and Symfony applications

## Install

```bash
composer require lustrace/ares2-client
```

You must also install a PSR-18 client + PSR-17 factories, e.g. Symfony HttpClient + Nyholm PSR-7:

```bash
composer require symfony/http-client symfony/psr-http-message-bridge nyholm/psr7
```

## Low-level client (raw ARES payloads)

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

## DDD-oriented API (typed domain objects)

```php
use Lustrace\Ares2\Application\EconomicSubjectService;
use Lustrace\Ares2\DTO\Pagination;
use Lustrace\Ares2\Infrastructure\Ares\AresEconomicSubjectRepository;

$repository = new AresEconomicSubjectRepository($ares);
$service = new EconomicSubjectService($repository);

$subject = $service->getByIco('00006947');
$searchResult = $service->search(['obchodniJmeno' => 'Ministerstvo'], new Pagination(0, 20));

foreach ($searchResult->items as $item) {
    echo (string) $item->ico . ' ' . ($item->name ?? '') . PHP_EOL;
}
```

## Symfony usage

Because all abstractions are interfaces + immutable value objects, the library is easy to wire in Symfony services:

```yaml
# config/services.yaml
services:
  Lustrace\Ares2\AresClient:
    arguments:
      $httpClient: '@psr18.http_client'
      $requestFactory: '@nyholm.psr7.psr17_factory'
      $streamFactory: '@nyholm.psr7.psr17_factory'

  Lustrace\Ares2\Domain\Repository\EconomicSubjectRepositoryInterface:
    class: Lustrace\Ares2\Infrastructure\Ares\AresEconomicSubjectRepository
    arguments:
      $client: '@Lustrace\Ares2\AresClient'

  Lustrace\Ares2\Application\EconomicSubjectService:
    arguments:
      $repository: '@Lustrace\Ares2\Domain\Repository\EconomicSubjectRepositoryInterface'
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


## Lustration (multi-source, "all relevant data")

For a lustration / due-diligence workflow you typically:

1) Resolve the target IČO (directly or via CORE search by `obchodniJmeno`)
2) Fetch subject detail from CORE and all relevant datasets (`RES`, `VR`, `RZP`, ...)

This package includes a small orchestrator that does exactly that:

```php
use Lustrace\Ares2\Application\AresLustrationService;
use Lustrace\Ares2\DTO\Lustration\LustrationOptions;
use Lustrace\Ares2\DTO\Lustration\LustrationQuery;

$lustration = new AresLustrationService($client);

$result = $lustration->run(
    LustrationQuery::forCompanyName('Ministerstvo financí'),
    new LustrationOptions(
        maxTargets: 5,
        relevantSourcesOnly: true, // uses CORE->seznamRegistraci to avoid needless 404s
    )
);

foreach ($result->subjects as $subject) {
    echo $subject->ico . PHP_EOL;
    foreach ($subject->bySource as $sourceKey => $sourceResult) {
        echo " - {$sourceKey}: {$sourceResult->status->value}" . PHP_EOL;
    }
}
```

### Person name search – important limitation

ARES CORE filter officially supports searching by **obchodní jméno** and some other company attributes.
It does **not** provide a public "person to companies" search across statutory bodies.

So `LustrationQuery::forPersonName()` is implemented as a *best-effort OSVČ / name-as-trade-name* lookup:
it tries a few `obchodniJmeno` variants like `"Jan Novák"`, `"Novák Jan"`, `"Novák, Jan"` and then proceeds with the same multi-source fetch.

If you need **true** person-to-company linking (statutory bodies across companies), you will need another legally available source (e.g. licensed commercial registry data) and treat ARES as a data enrichment layer.

## Resilience: retries + client-side load balancing

ARES can rate-limit (429) or sporadically fail (5xx). The client supports:

- Exponential backoff retries for 429/5xx (configurable)
- Round-robin base URI selection + short cooldown on failed endpoints

```php
use Lustrace\Ares2\Infrastructure\Http\RetryPolicy;

$client = new AresClient(
    httpClient: $httpClient,
    requestFactory: $requestFactory,
    streamFactory: $streamFactory,
    baseUri: 'https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/',
    baseUris: [
        'https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/', // add more if you have them (proxy layer, mirrors, ...)
    ],
    retryPolicy: new RetryPolicy(maxRetries: 2),
    defaultHeaders: [
        'User-Agent' => 'lustrace/1.0 (+https://your-domain.example)',
        // 'Authorization' => 'Bearer ...', // if your integration requires credentials
    ],
);
```



## Symfony integration

You can wire it using Symfony HttpClient (PSR-18) + Nyholm PSR-17 factory:

```php
// services.yaml (example)
services:
  Nyholm\Psr7\Factory\Psr17Factory: ~

  Lustrace\Ares2\AresClient:
    arguments:
      $httpClient: '@Psr\Http\Client\ClientInterface'
      $requestFactory: '@Nyholm\Psr7\Factory\Psr17Factory'
      $streamFactory: '@Nyholm\Psr7\Factory\Psr17Factory'
      $baseUri: '%env(ARES_BASE_URI)%'
      $defaultHeaders:
        User-Agent: 'lustrace/1.0'
```

Then inject `AresClient` or `AresLustrationService` into your Messenger handlers.

