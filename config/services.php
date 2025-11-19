<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'github' => [
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect' => env('GITHUB_REDIRECT_URL'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URL'),
    ],

    'regapp' => [
        'client_id' => env('NFDI_CLIENT_ID'),
        'client_secret' => env('NFDI_CLIENT_SECRET'),
        'redirect' => env('NFDI_REDIRECT_URL'),
    ],

    'cheminf' => [
        'api_url' => env('CM_PUBLIC_API', 'https://api.cheminf.studio/latest/'),
        'internal_api_url' => env('API_URL', 'https://api.cheminf.studio/latest/'),
    ],

    'citation' => [
        'europepmc_url' => env('EUROPEPMC_WS_API'),
        'crossref_url' => env('CROSSREF_WS_API'),
        'datacite_url' => env('DATACITE_WS_API'),
    ],

    'tawk' => [
        'url' => env('TAWK_URL'),
    ],

];
