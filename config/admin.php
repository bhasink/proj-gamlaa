<?php

return [
    /*
    |----------------------------------------------------------------------
    | Operator credentials
    |----------------------------------------------------------------------
    | A single account guards the admin panel. Override in .env for
    | production — never ship with the defaults.
    */
    'email'         => env('ADMIN_EMAIL', 'admin@gamlaa.com'),
    'password'      => env('ADMIN_PASSWORD', 'gamlaa'),
    'password_hash' => env('ADMIN_PASSWORD_HASH'),

    /*
    |----------------------------------------------------------------------
    | Display name shown in the topbar pill
    */
    'name' => env('ADMIN_NAME', 'Admin'),
];
