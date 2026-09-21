<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Enums;

/**
 * What a webhook endpoint listens to, which limits the events it may take.
 */
enum WebhookEndpointType: string
{
    case Document = 'DOCUMENT';
    case Signature = 'SIGNATURE';
    case Member = 'MEMBER';
}
