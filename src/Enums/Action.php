<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Enums;

/**
 * What a signer is asked to do. `SIGN_AS_PART` and `SIGN_AS_INTERVENER` are
 * deprecated by Autentique and not offered.
 */
enum Action: string
{
    case Sign = 'SIGN';
    case Approve = 'APPROVE';
    case Recognize = 'RECOGNIZE';
    case SignAsWitness = 'SIGN_AS_A_WITNESS';
    case AcknowledgeReceipt = 'ACKNOWLEDGE_RECEIPT';
    case EndorseInBlack = 'ENDORSE_IN_BLACK';
    case EndorseInWhite = 'ENDORSE_IN_WHITE';
}
