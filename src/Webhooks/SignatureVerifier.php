<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Webhooks;

/**
 * Whether a webhook's body was signed with the endpoint's secret.
 *
 * `X-Autentique-Signature` is the hex HMAC SHA-256 of the **raw** body. The body
 * is never decoded and re-encoded first: a check that depends on key order or
 * whitespace fails on real traffic
 * (docs/decisions/0007-webhooks-are-verified-and-idempotency-is-the-applications.md).
 */
final class SignatureVerifier
{
    public const string HEADER = 'X-Autentique-Signature';

    /**
     * Compared in constant time with `hash_equals()`, so the time taken says
     * nothing about how much of a forged signature was right.
     */
    public static function verify(string $body, ?string $signature, #[\SensitiveParameter] string $secret): bool
    {
        if ($signature === null || $signature === '' || $secret === '') {
            return false;
        }

        return hash_equals(self::sign($body, $secret), strtolower(trim($signature)));
    }

    /**
     * The signature Autentique would send for this body.
     */
    public static function sign(string $body, #[\SensitiveParameter] string $secret): string
    {
        return hash_hmac('sha256', $body, $secret);
    }
}
