<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Enums;

/**
 * Every event Autentique posts to a webhook endpoint.
 */
enum WebhookEventType: string
{
    case DocumentCreated = 'document.created';
    case DocumentUpdated = 'document.updated';
    case DocumentDeleted = 'document.deleted';
    case DocumentFinished = 'document.finished';
    case SignatureCreated = 'signature.created';
    case SignatureUpdated = 'signature.updated';
    case SignatureDeleted = 'signature.deleted';
    case SignatureViewed = 'signature.viewed';
    case SignatureAccepted = 'signature.accepted';
    case SignatureRejected = 'signature.rejected';
    case SignatureBiometricApproved = 'signature.biometric_approved';
    case SignatureBiometricUnapproved = 'signature.biometric_unapproved';
    case SignatureBiometricReset = 'signature.biometric_reset';
    case SignatureBiometricRejected = 'signature.biometric_rejected';
    case SignatureDeliveryFailed = 'signature.delivery_failed';
    case MemberCreated = 'member.created';
    case MemberDeleted = 'member.deleted';

    /**
     * Whether the event is about a document, a signature or a member.
     */
    public function resource(): string
    {
        return explode('.', $this->value, 2)[0];
    }
}
