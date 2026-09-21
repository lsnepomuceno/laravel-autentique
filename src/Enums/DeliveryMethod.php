<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Enums;

/**
 * How a signer receives the request to sign.
 */
enum DeliveryMethod: string
{
    case Email = 'DELIVERY_METHOD_EMAIL';
    case Link = 'DELIVERY_METHOD_LINK';
    case Sms = 'DELIVERY_METHOD_SMS';
    case Whatsapp = 'DELIVERY_METHOD_WHATSAPP';
}
