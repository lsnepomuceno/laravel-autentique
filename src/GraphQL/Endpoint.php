<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\GraphQL;

/**
 * Which of Autentique's GraphQL endpoints an operation is sent to.
 *
 * The value is the configuration key holding the endpoint's URL, so the client
 * reads the URL and never hard codes it.
 */
enum Endpoint: string
{
    /** `https://api.autentique.com.br/v2/graphql`, every plan. */
    case Standard = 'url';

    /** `https://api.autentique.com.br/v2/graphql/corporate`, the Corporate plan only. */
    case Corporate = 'corporate_url';
}
