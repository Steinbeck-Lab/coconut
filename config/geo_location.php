<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Country name aliases
    |--------------------------------------------------------------------------
    |
    | Maps non-standard trailing segments in geo location names to ISO 3166-1
    | alpha-2 codes understood by rinvex/countries.
    |
    */
    'aliases' => [
        'usa' => 'US',
        'u.s.a.' => 'US',
        'u.s.a' => 'US',
        'uk' => 'GB',
        'u.k.' => 'GB',
        'south korea' => 'KR',
        'north korea' => 'KP',
        'russia' => 'RU',
        'czech republic' => 'CZ',
        'ivory coast' => 'CI',
        'vietnam' => 'VN',
        'bolivia' => 'BO',
        'venezuela' => 'VE',
        'iran' => 'IR',
        'syria' => 'SY',
        'tanzania' => 'TZ',
        'laos' => 'LA',
        'moldova' => 'MD',
        'western australia' => 'AU',
        'south australia' => 'AU',
        'northern territory' => 'AU',
        'queensland' => 'AU',
        'new south wales' => 'AU',
        'victoria' => 'AU',
        'tasmania' => 'AU',
        'canary islands' => 'ES',
        'hawaii' => 'US',
        'guam' => 'GU',
        'puerto rico' => 'PR',
        'french polynesia' => 'PF',
        'marshall islands' => 'MH',
        'micronesia' => 'FM',
        'palau' => 'PW',
    ],

    /*
    |--------------------------------------------------------------------------
    | Mexican state names (parentheses format: City(State))
    |--------------------------------------------------------------------------
    */
    'mexican_states' => [
        'estado de mexico',
        'ciudad de mexico',
        'distrito federal',
        'morelos',
        'puebla',
        'chihuahua',
        'guerrero',
        'veracruz',
        'sonora',
        'durango',
        'michoacan',
        'michoac?n',
        'nuevo leon',
        'nuevo le?n',
        'san luis potosi',
        'oaxaca',
        'jalisco',
        'yucatan',
        'yucat?n',
        'tabasco',
        'campeche',
        'quintana roo',
        'baja california',
        'sinaloa',
        'colima',
        'nayarit',
        'zacatecas',
        'aguascalientes',
        'guanajuato',
        'queretaro',
        'hidalgo',
        'tlaxcala',
        'chiapas',
        'tamaulipas',
        'coahuila',
    ],

    /*
    |--------------------------------------------------------------------------
    | Substring matching
    |--------------------------------------------------------------------------
    */
    'substring_min_length' => 4,

    /*
    |--------------------------------------------------------------------------
    | Geocoding skip patterns
    |--------------------------------------------------------------------------
    */
    'geocode_skip_pattern' => '/not specified|no espec|unspecified|unknown/i',

    /*
    |--------------------------------------------------------------------------
    | Nominatim cache TTL (seconds)
    |--------------------------------------------------------------------------
    */
    'nominatim_cache_ttl' => 60 * 60 * 24 * 30,

];
