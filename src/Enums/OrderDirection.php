<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Enums;

enum OrderDirection: string
{
    case Ascending = 'ASC';
    case Descending = 'DESC';
}
