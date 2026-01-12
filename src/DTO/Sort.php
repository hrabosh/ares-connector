<?php

declare(strict_types=1);

namespace Lustrace\Ares2\DTO;

/**
 * Optional sorting. The actual sort keys depend on the ARES2 service.
 */
final readonly class Sort
{
    public function __construct(
        public string $field,
        public string $direction = 'ASC', // 'ASC'|'DESC'
    ) {
        $dir = strtoupper($direction);
        if (!in_array($dir, ['ASC', 'DESC'], true)) {
            throw new \InvalidArgumentException('Sort direction must be ASC or DESC.');
        }
    }

    public function toArray(): array
    {
        return [
            'pole' => $this->field,
            'smer' => strtoupper($this->direction),
        ];
    }
}
