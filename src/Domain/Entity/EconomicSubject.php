<?php

declare(strict_types=1);

namespace Lustrace\Ares2\Domain\Entity;

use Lustrace\Ares2\Domain\ValueObject\Ico;
use Lustrace\Ares2\Domain\ValueObject\IcoNormalizer;

final readonly class EconomicSubject
{
    /**
     * @param array<string,mixed> $raw
     */
    public function __construct(
        public Ico $ico,
        public ?string $name,
        public array $raw = [],
    ) {}

    /**
     * @param array<string,mixed> $data
     */
    public static function fromAresData(array $data): self
    {
        $icoValue = '';

        if (isset($data['ico']) && (is_string($data['ico']) || is_int($data['ico']))) {
            $icoValue = (string) $data['ico'];
        } elseif (isset($data['icoId'])) {
            // some ARES responses use icoId as an identifier
            if (is_string($data['icoId']) || is_int($data['icoId'])) {
                $icoValue = (string) $data['icoId'];
            } elseif (is_array($data['icoId']) && isset($data['icoId']['ico'])) {
                $icoValue = (string) $data['icoId']['ico'];
            }
        }

        $icoValue = IcoNormalizer::normalize($icoValue);

        $name = null;
        foreach (['obchodniJmeno', 'nazev', 'jmeno'] as $key) {
            if (isset($data[$key]) && is_string($data[$key]) && $data[$key] !== '') {
                $name = $data[$key];
                break;
            }
        }

        return new self(new Ico($icoValue), $name, $data);
    }
}
