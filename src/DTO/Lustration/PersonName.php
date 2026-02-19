<?php

declare(strict_types=1);

namespace Lustrace\Ares2\DTO\Lustration;

final readonly class PersonName
{
    public function __construct(
        public string $firstName,
        public string $lastName,
    ) {
        $this->firstName = trim($firstName);
        $this->lastName = trim($lastName);

        if ($this->firstName === '' || $this->lastName === '') {
            throw new \InvalidArgumentException('PersonName requires both firstName and lastName.');
        }
    }

    public function asDisplayName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    /**
     * Generate a small set of name variants useful for OSVČ / obchodní jméno lookups.
     *
     * @return list<string>
     */
    public function variants(): array
    {
        $a = $this->asDisplayName();
        $b = $this->lastName . ' ' . $this->firstName;
        $c = $this->lastName . ', ' . $this->firstName;

        $variants = array_values(array_unique([$a, $b, $c]));
        return $variants;
    }
}
