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

];
