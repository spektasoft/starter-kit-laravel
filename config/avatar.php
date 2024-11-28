<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Enabled
    |--------------------------------------------------------------------------
    |
    | Determines whether avatars will be generated directly from the server.
    | Setting it to false disables this functionality. Disabling it will
    | improve performance, but at the cost of third-party reliance.
    |
    */

    'enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Colors
    |--------------------------------------------------------------------------
    |
    | This option defines the default colors used for avatar appearance.
    | You may change these values to suit your application's design.
    | The default are primary color background with white text.
    |
    */

    'colors' => [
        'background' => 'primary',
        'foreground' => '#ffffff',
    ],
];
