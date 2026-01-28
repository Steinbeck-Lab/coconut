<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default DOI Service Provider
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default doi service provider that should be used
    | by the framework.
    |
    */

    'default' => env('DOI_PROVIDER', 'datacite'),

    /*
    |--------------------------------------------------------------------------
    | DataCite Options
    |--------------------------------------------------------------------------
    |
    | Here you may specify the configuration options that should be used when
    | passwords are hashed using the Argon algorithm. These will allow you
    | to control the amount of time it takes to hash the given password.
    |
    */

    'datacite' => [
        'username' => env('DATACITE_USERNAME'),
        'secret' => env('DATACITE_SECRET'),
        'prefix' => env('DATACITE_PREFIX'),
        'endpoint' => env('DATACITE_ENDPOINT', 'api.test.datacite.org'),
        'host' => env('DOI_HOST'),
        'contributor_types' => env('DATACITE_CONTRIBUTOR_TYPE', ['ContactPerson', 'DataCollector', 'DataCurator', 'DataManager', 'Distributor', 'Editor', 'HostingInstitution', 'Producer', 'ProjectLeader', 'ProjectManager', 'ProjectMember', 'RegistrationAgency', 'RegistrationAuthority', 'RelatedPerson', 'Researcher', 'ResearchGroup', 'RightsHolder', 'Sponsor', 'Supervisor', 'WorkPackageLeader', 'Other']),
    ],

];
