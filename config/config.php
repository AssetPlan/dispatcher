<?php

/*
 * You can place your custom package configuration in here.
 */
return [
    'url' => env('DISPATCHER_BACKEND_URL', 'http://localhost'),

    'secret' => env('DISPATCHER_BACKEND_SECRET', 'secret'),

    'is_backend' => env('DISPATCHER_IS_BACKEND', false)
];
