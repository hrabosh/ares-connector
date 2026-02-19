<?php

declare(strict_types=1);

namespace Lustrace\Ares2\Application;

use Lustrace\Ares2\AresClient;
use Lustrace\Ares2\DTO\Lustration\LustrationOptions;
use Lustrace\Ares2\DTO\Lustration\LustrationQuery;
use Lustrace\Ares2\DTO\Lustration\LustrationRunResult;
use Lustrace\Ares2\DTO\Lustration\SourceFetchResult;
use Lustrace\Ares2\DTO\Lustration\SourceFetchStatus;
use Lustrace\Ares2\DTO\Lustration\SubjectLustrationResult;
use Lustrace\Ares2\DTO\Pagination;
use Lustrace\Ares2\DTO\SearchRequest;
use Lustrace\Ares2\Enum\DataSource;
use Lustrace\Ares2\Exception\AresApiException;
use Lustrace\Ares2\Exception\AresTransportException;

final readonly class AresLustrationService
{
    public function __construct(private AresClient $client) {}

    public function run(LustrationQuery $query, ?LustrationOptions $options = null): LustrationRunResult
    {
        $options ??= new LustrationOptions();

        $targets = [];
        $searchRaw = null;

        if ($query->type->value === 'ico' && $query->ico !== null) {
            $targets = [$query->ico];
        } else {
            $pagination = $options->searchPagination ?? new Pagination(0, min($options->maxTargets, 50));

            if ($query->type->value === 'company_name' && $query->companyName !== null) {
                $resp = $this->client->searchEconomicSubjects(new SearchRequest(
                    criteria: ['obchodniJmeno' => $query->companyName],
                    pagination: $pagination,
                    sort: [],
                ));
                $searchRaw = $resp->raw;
                $targets = $this->extractIcosFromSearchItems($resp->items, $options->maxTargets);
            }

            if ($query->type->value === 'person_name' && $query->personName !== null) {
                // Best-effort: ARES CORE search filter supports "obchodniJmeno",
                // so we try OSVČ / name-as-trade-name variants.
                $allItems = [];
                $rawParts = [];

                foreach ($query->personName->variants() as $variant) {
                    $resp = $this->client->searchEconomicSubjects(new SearchRequest(
                        criteria: ['obchodniJmeno' => $variant],
                        pagination: $pagination,
                        sort: [],
                    ));
                    $rawParts[] = $resp->raw;
                    foreach ($resp->items as $it) {
                        $allItems[] = $it;
                    }
                }

                $searchRaw = ['variants' => $rawParts];
                $targets = $this->extractIcosFromSearchItems($allItems, $options->maxTargets);
            }
        }

        $subjects = [];
        foreach ($targets as $ico) {
            $subjects[] = $this->lustrateIco($ico, $options);
        }

        return new LustrationRunResult($query, $subjects, $searchRaw);
    }

    private function lustrateIco(string $ico, LustrationOptions $options): SubjectLustrationResult
    {
        $bySource = [];
        $name = null;

        $sources = $options->resolvedSources();

        $coreData = null;
        if (in_array(DataSource::CORE, $sources, true)) {
            $coreResult = $this->fetchFromSource(DataSource::CORE, $ico);
            $bySource[DataSource::CORE->value] = $coreResult;

            if ($coreResult->status === SourceFetchStatus::OK && is_array($coreResult->data)) {
                $coreData = $coreResult->data;
                $name = $this->extractName($coreData);
            }
        }

        // Determine "relevant" sources from CORE registration list if requested.
        if ($options->relevantSourcesOnly && $coreData !== null) {
            $relevant = $this->resolveRelevantSourcesFromCore($coreData);

            $sources = array_values(array_filter(
                $sources,
                static fn (DataSource $s): bool => $s === DataSource::CORE || in_array($s, $relevant, true),
            ));
        }

        foreach ($sources as $source) {
            if ($source === DataSource::CORE) {
                continue;
            }
            $bySource[$source->value] = $this->fetchFromSource($source, $ico);
        }

        return new SubjectLustrationResult(
            ico: $ico,
            name: $name,
            bySource: $bySource,
        );
    }

    private function fetchFromSource(DataSource $source, string $ico): SourceFetchResult
    {
        try {
            $data = $this->client->getEconomicSubjectFromSource($ico, $source);

            return new SourceFetchResult(
                source: $source,
                status: SourceFetchStatus::OK,
                data: $data,
                error: null,
                httpStatus: 200,
            );
        } catch (AresApiException $e) {
            $status = match ($e->httpStatus) {
                401 => SourceFetchStatus::UNAUTHORIZED,
                403 => SourceFetchStatus::FORBIDDEN,
                404 => SourceFetchStatus::NOT_FOUND,
                default => SourceFetchStatus::ERROR,
            };

            return new SourceFetchResult(
                source: $source,
                status: $status,
                data: null,
                error: [
                    'message' => $e->getMessage(),
                    'errorCode' => $e->errorCode,
                    'subCode' => $e->subCode,
                    'errorDescription' => $e->errorDescription,
                ],
                httpStatus: $e->httpStatus,
            );
        } catch (AresTransportException $e) {
            return new SourceFetchResult(
                source: $source,
                status: SourceFetchStatus::ERROR,
                data: null,
                error: [
                    'message' => $e->getMessage(),
                ],
                httpStatus: null,
            );
        }
    }

    /**
     * @param list<array<string,mixed>> $items
     * @return list<string>
     */
    private function extractIcosFromSearchItems(array $items, int $limit): array
    {
        $out = [];

        foreach ($items as $it) {
            if (!is_array($it)) {
                continue;
            }

            $ico = null;

            if (isset($it['ico']) && (is_string($it['ico']) || is_int($it['ico']))) {
                $ico = (string) $it['ico'];
            } elseif (isset($it['icoId']) && (is_string($it['icoId']) || is_int($it['icoId']))) {
                $ico = (string) $it['icoId'];
            } elseif (isset($it['icoId']) && is_array($it['icoId']) && isset($it['icoId']['ico'])) {
                $ico = (string) $it['icoId']['ico'];
            }

            if ($ico === null) {
                continue;
            }

            $ico = \Lustrace\Ares2\Domain\ValueObject\IcoNormalizer::normalize($ico);
            if ($ico === '' || !preg_match('/^\d{8}$/', $ico)) {
                continue;
            }

            $out[$ico] = $ico;
            if (count($out) >= $limit) {
                break;
            }
        }

        return array_values($out);
    }

    private function extractName(array $data): ?string
    {
        foreach (['obchodniJmeno', 'nazev', 'jmeno'] as $key) {
            if (isset($data[$key]) && is_string($data[$key]) && $data[$key] !== '') {
                return $data[$key];
            }
        }
        return null;
    }

    /**
     * Use CORE's "seznamRegistraci" to decide which dataset endpoints are likely to have a record.
     *
     * @return list<DataSource>
     */
    private function resolveRelevantSourcesFromCore(array $core): array
    {
        $reg = $core['seznamRegistraci'] ?? null;
        if (!is_array($reg)) {
            return [];
        }

        $map = [
            'stavZdrojeRes' => DataSource::RES,
            'stavZdrojeVr' => DataSource::VR,
            'stavZdrojeRzp' => DataSource::RZP,
            'stavZdrojeRcns' => DataSource::RCNS,
            'stavZdrojeRos' => DataSource::ROS,
            'stavZdrojeRpsh' => DataSource::RPSH,
            'stavZdrojeCeu' => DataSource::CEU,
            'stavZdrojeRs' => DataSource::RS,
            'stavZdrojeSzr' => DataSource::SZR,
            'stavZdrojeNrpzs' => DataSource::NRPZS,
        ];

        $out = [];
        foreach ($map as $key => $source) {
            if (!array_key_exists($key, $reg)) {
                continue;
            }
            $val = $reg[$key];

            // any non-null value indicates the dataset is at least known for this subject
            if ($val !== null && $val !== '') {
                $out[] = $source;
            }
        }

        return $out;
    }
}
