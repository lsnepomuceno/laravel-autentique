<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Enums;

/**
 * An additional identity verification a signer goes through. Each costs
 * verification credits.
 */
enum VerificationType: string
{
    case Sms = 'SMS';
    case Manual = 'MANUAL';
    case Upload = 'UPLOAD';
    case Live = 'LIVE';
    case PfFacial = 'PF_FACIAL';
    case BiometricAndTextExtraction = 'BIOMETRIC_AND_TEXT_EXTRACTION';
    case LivenessAndTextExtraction = 'LIVENESS_AND_TEXT_EXTRACTION';
}
