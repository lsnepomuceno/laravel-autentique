<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Scalars only
|--------------------------------------------------------------------------
|
| Every value in this file is a string, a number, a boolean or null. An enum
| instance or an object here would break `php artisan config:cache`, which
| serialises the array, and it would break in the application rather than in
| this package's suite.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | API token
    |--------------------------------------------------------------------------
    |
    | Created in the Autentique dashboard, under the API settings. It is sent as
    | a Bearer token on every request and never written to an exception message
    | or a log.
    |
    */

    'token' => env('AUTENTIQUE_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Endpoint
    |--------------------------------------------------------------------------
    |
    | The GraphQL endpoint of the API v2. There is no separate sandbox endpoint:
    | sandbox is an argument of the calls that accept it, below.
    |
    */

    'url' => env('AUTENTIQUE_URL', 'https://api.autentique.com.br/v2/graphql'),

    /*
    |--------------------------------------------------------------------------
    | Sandbox by default
    |--------------------------------------------------------------------------
    |
    | Whether documents are created as sandbox documents when a call does not
    | say. Sandbox documents are not billed, carry no legal validity, and are
    | deleted by Autentique after a few days.
    |
    | Leave it false in production, where a forgotten variable should create
    | real documents rather than invalid ones, and set it in local and staging.
    |
    */

    'sandbox' => (bool) env('AUTENTIQUE_SANDBOX', false),

    /*
    |--------------------------------------------------------------------------
    | Timeout
    |--------------------------------------------------------------------------
    |
    | Seconds to wait for a response. An upload of a large document is the
    | slowest request the package makes.
    |
    */

    'timeout' => (int) env('AUTENTIQUE_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Retrying a refused request
    |--------------------------------------------------------------------------
    |
    | Only HTTP 429 is retried: Autentique refused the request, so nothing was
    | processed. A timeout or a server error may have been processed, and a
    | mutation repeated after one can duplicate a billed document, so neither
    | is ever retried here.
    |
    | times  how many retries after the first attempt; 0 turns retrying off
    | sleep  milliseconds to wait, times the attempt number, when Autentique
    |        does not say how long in a Retry-After header
    |
    */

    'retry' => [
        'times' => (int) env('AUTENTIQUE_RETRY_TIMES', 2),
        'sleep' => (int) env('AUTENTIQUE_RETRY_SLEEP', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhooks
    |--------------------------------------------------------------------------
    |
    | secret       The endpoint's secret, shown in the dashboard when the
    |              endpoint is registered. Every webhook is verified against it.
    |
    | path         Where the package listens, `webhooks/autentique` for
    |              example. Null registers no route: put the
    |              VerifyAutentiqueSignature middleware on your own instead.
    |
    | middleware   Middleware for that route, besides the signature check. Not
    |              `web`: Autentique sends no CSRF token.
    |
    | deduplicate  Seconds to remember each event id, dropping a delivery of an
    |              event already received. Null leaves duplicates to your
    |              listeners. Autentique retries for about eight minutes.
    |
    | cache_store  The cache store remembering them. Null uses the default.
    |
    */

    'webhooks' => [
        'secret' => env('AUTENTIQUE_WEBHOOK_SECRET'),
        'path' => env('AUTENTIQUE_WEBHOOK_PATH'),
        'middleware' => [],
        'deduplicate' => env('AUTENTIQUE_WEBHOOK_DEDUPLICATE'),
        'cache_store' => env('AUTENTIQUE_WEBHOOK_CACHE_STORE'),
    ],

];
