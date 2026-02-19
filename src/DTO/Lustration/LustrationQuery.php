<?php

declare(strict_types=1);

namespace Lustrace\Ares2\DTO\Lustration;

use Lustrace\Ares2\Domain\ValueObject\IcoNormalizer;

final readonly class LustrationQuery
{
    private function __construct(
        public QueryType $type,
        public string $raw,
        public ?string $ico = null,
        public ?string $companyName = null,
        public ?PersonName $personName = null,
    ) {}

    public static function forIco(string $ico): self
    {
        $norm = IcoNormalizer::normalize($ico);
        return new self(QueryType::ICO, $ico, ico: $norm);
    }

    public static function forCompanyName(string $name): self
    {
        $name = trim($name);
        if ($name === '') {
            throw new \InvalidArgumentException('Company name cannot be empty.');
        }
        return new self(QueryType::COMPANY_NAME, $name, companyName: $name);
    }

    public static function forPersonName(string $firstName, string $lastName): self
    {
        $pn = new PersonName($firstName, $lastName);
        return new self(QueryType::PERSON_NAME, $pn->asDisplayName(), personName: $pn);
    }

    /**
     * Best-effort parser for a single user string:
     * - 1-8 digits -> IČO (left-padded)
     * - otherwise -> company name
     */
    public static function parse(string $userInput): self
    {
        $s = trim($userInput);
        if ($s === '') {
            throw new \InvalidArgumentException('Query cannot be empty.');
        }

        if (preg_match('/^\d{1,8}$/', $s) === 1) {
            return self::forIco($s);
        }

        return self::forCompanyName($s);
    }
}
