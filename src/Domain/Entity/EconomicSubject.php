<?php

declare(strict_types=1);

namespace Lustrace\Ares2\Domain\Entity;

use Lustrace\Ares2\Domain\ValueObject\Ico;

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
        $icoValue = isset($data['ico']) ? (string) $data['ico'] : '';
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
