<?php

declare(strict_types=1);

namespace Lustrace\Ares2\DTO\Lustration;

enum SourceFetchStatus: string
{
    case OK = 'ok';
    case NOT_FOUND = 'not_found';
    case FORBIDDEN = 'forbidden';
    case UNAUTHORIZED = 'unauthorized';
    case ERROR = 'error';
}
