<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Enums;

/**
 * How a webhook endpoint receives its events.
 */
enum WebhookFormat: string
{
    case Json = 'JSON';
    case UrlEncoded = 'URLENCODED';
}
