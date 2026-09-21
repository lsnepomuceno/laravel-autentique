<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data;

use Carbon\CarbonImmutable;
use LSNepomuceno\LaravelAutentique\Exceptions\UnexpectedResponse;
use LSNepomuceno\LaravelAutentique\Support\Payload;

/**
 * An access token and the refresh token that replaces it.
 *
 * **Both are credentials**, and **a refresh replaces both**: store the pair
 * from every refresh, and never use a refresh token twice.
 */
final readonly class OAuthTokens
{
    public function __construct(
        #[\SensitiveParameter]
        public string $accessToken,
        #[\SensitiveParameter]
        public ?string $refreshToken,
        public ?string $tokenType,
        public ?CarbonImmutable $expiresAt,
    ) {}

    /**
     * @throws UnexpectedResponse
     */
    public static function fromPayload(Payload $payload): self
    {
        $expiresIn = $payload->nullableInt('expires_in');

        return new self(
            accessToken: $payload->string('access_token'),
            refreshToken: $payload->nullableString('refresh_token'),
            tokenType: $payload->nullableString('token_type'),
            expiresAt: $expiresIn === null ? null : CarbonImmutable::now()->addSeconds($expiresIn),
        );
    }

    public function hasExpired(): bool
    {
        return $this->expiresAt !== null && $this->expiresAt->isPast();
    }
}
