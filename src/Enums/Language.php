<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Enums;

/**
 * The language of the signing interface and the messages sent. It never
 * translates the document itself.
 */
enum Language: string
{
    case PortugueseBrazil = 'pt-BR';
    case EnglishUnitedStates = 'en-US';
    case SpanishMexico = 'es-MX';
}
