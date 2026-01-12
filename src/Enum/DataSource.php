<?php

declare(strict_types=1);

namespace Lustrace\Ares2\Enum;

/**
 * ARES2 exposes multiple "public services" (datasets) as separate endpoints.
 * This enum provides a conservative, extendable mapping.
 */
enum DataSource: string
{
    case CORE = 'ekonomicke-subjekty';
    case RES = 'ekonomicke-subjekty-res';
    case VR  = 'ekonomicke-subjekty-vr';
    case RZP = 'ekonomicke-subjekty-rzp';
    case RCNS = 'ekonomicke-subjekty-rcns';

    public function detailPath(string $ico): string
    {
        $ico = trim($ico);
        return sprintf('%s/%s', $this->value, $ico);
    }

    public function searchPath(): string
    {
        // Not all endpoints necessarily support search; keep it configurable.
        return sprintf('%s/vyhledat', $this->value);
    }
}
