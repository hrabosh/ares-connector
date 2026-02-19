<?php

declare(strict_types=1);

namespace Lustrace\Ares2\DTO\Lustration;

enum QueryType: string
{
    case ICO = 'ico';
    case COMPANY_NAME = 'company_name';
    case PERSON_NAME = 'person_name';
}
