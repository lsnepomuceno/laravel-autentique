<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data;

use LSNepomuceno\LaravelAutentique\Support\Payload;

/**
 * One figure per billed action: a price in a pricing, a count in a day of usage.
 * Autentique leaves an action it has nothing for as null.
 */
final readonly class ApiUsageItems
{
    public function __construct(
        public ?float $createDocument,
        public ?float $email,
        public ?float $sms,
        public ?float $whatsapp,
        public ?float $whatsappOtp,
        public ?float $documentsQuery,
        public ?float $link,
        public ?float $signPhoneSecurityVerification,
        public ?float $webhook,
    ) {}

    public static function fromPayload(Payload $payload): self
    {
        return new self(
            createDocument: $payload->nullableFloat('create_document'),
            email: $payload->nullableFloat('email'),
            sms: $payload->nullableFloat('sms'),
            whatsapp: $payload->nullableFloat('whatsapp'),
            whatsappOtp: $payload->nullableFloat('whatsapp_otp'),
            documentsQuery: $payload->nullableFloat('documents_query'),
            link: $payload->nullableFloat('link'),
            signPhoneSecurityVerification: $payload->nullableFloat('sign_phone_security_verification'),
            webhook: $payload->nullableFloat('webhook'),
        );
    }
}
