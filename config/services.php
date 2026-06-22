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
        'oidc_base_url' => rtrim(env('NFDI_OIDC_BASE_URL', 'https://regapp.nfdi-aai.de/oidc/realms/nfdi/protocol/openid-connect'), '/'),
    ],

    'cheminf' => [
        'api_url' => env('CM_PUBLIC_API', 'https://api.cheminf.studio/latest/'),
        'internal_api_url' => env('API_URL', 'https://api.cheminf.studio/latest/'),
    ],

    'citation' => [
        'europepmc_url' => env('EUROPEPMC_WS_API', 'https://www.ebi.ac.uk/europepmc/webservices/rest/search'),
        'crossref_url' => env('CROSSREF_WS_API', 'https://api.crossref.org/works/'),
        'datacite_url' => env('DATACITE_WS_API', 'https://api.datacite.org/dois/'),
    ],

    'coconut' => [
        'public_url' => rtrim(env('COCONUT_PUBLIC_URL', 'https://coconut.naturalproducts.net'), '/'),
    ],

    'pubchem' => [
        'base_url' => rtrim(env('PUBCHEM_API_BASE', 'https://pubchem.ncbi.nlm.nih.gov/rest/pug'), '/'),
    ],

    'npclassifier' => [
        'url' => rtrim(env('NP_CLASSIFIER_URL', 'https://npclassifier.gnps2.org/classify'), '/'),
    ],

    'globalnames' => [
        'url' => env('GLOBALNAMES_API_URL', 'https://finder.globalnames.org/api/v1/find'),
    ],

    'ols' => [
        'base_url' => rtrim(env('OLS4_API_BASE', 'https://www.ebi.ac.uk/ols4/api'), '/'),
    ],

    'avatars' => [
        'url' => rtrim(env('UI_AVATARS_URL', 'https://ui-avatars.com/api'), '/'),
    ],

    'tawk' => [
        'url' => env('TAWK_URL'),
    ],

    'cas' => [
        'cas_key' => env('CAS_KEY'),
    ],

    'organism_taxonomy' => [
        'ols_base_uri' => env('ORGANISM_TAXONOMY_OLS_BASE_URI', 'https://www.ebi.ac.uk/ols4/api/v2/'),
        'gnf_finder_url' => env('ORGANISM_TAXONOMY_GNF_FINDER_URL', 'https://finder.globalnames.org/api/v1/find'),
        'gnf_verifier_url' => env('ORGANISM_TAXONOMY_GNF_VERIFIER_URL', 'https://verifier.globalnames.org/api/v1/verifications'),
        'throttle_ms' => (int) env('ORGANISM_TAXONOMY_THROTTLE_MS', 200),
        'http_timeout' => (int) env('ORGANISM_TAXONOMY_HTTP_TIMEOUT', 30),
        'require_exact_gnf_match' => filter_var(
            env('ORGANISM_TAXONOMY_REQUIRE_EXACT_MATCH', true),
            FILTER_VALIDATE_BOOL,
        ),
        'batch_size' => max(1, (int) env('ORGANISM_TAXONOMY_BATCH_SIZE', 25)),
        'parallel_requests' => max(1, (int) env('ORGANISM_TAXONOMY_PARALLEL_REQUESTS', 4)),
        'apply_curation_on_miss' => filter_var(
            env('ORGANISM_TAXONOMY_APPLY_CURATION_ON_MISS', true),
            FILTER_VALIDATE_BOOL,
        ),
        'tree_cache_store' => env('ORGANISM_TAXONOMY_TREE_CACHE_STORE', 'file'),
        'tree_cache_hours' => max(1, (int) env('ORGANISM_TAXONOMY_TREE_CACHE_HOURS', 2)),
    ],

];
