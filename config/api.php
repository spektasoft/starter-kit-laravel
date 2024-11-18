<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Artisan
    |--------------------------------------------------------------------------
    |
    | Determines whether Artisan commands are allowed to run via the API.
    | Setting it to false disables this functionality. Disabling it can
    | help improve security by limiting potential entry points.
    |
    */

    'artisan' => false,

    /*
    |--------------------------------------------------------------------------
    | API Key
    |--------------------------------------------------------------------------
    |
    | The API key required for authentication purpose. This should be set
    | to a random string to ensure that all commands are secure. This
    | helps prevent unauthorized access to the API.
    |
    */

    'key' => env('API_KEY', null),
];
