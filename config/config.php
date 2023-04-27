<?php

/*
 * You can place your custom package configuration in here.
 */
return [
    'url' => env('DISPATCHER_BACKEND_URL', 'http://localhost'),

    'secret' => env('DISPATCHER_BACKEND_SECRET', 'secret'),

    'is_backend' => env('DISPATCHER_IS_BACKEND', false),

    /*
     * You can register aliases to make it easier to dispatch your jobs.
     * These aliases only need to be registered in the backend server and are optional.
     *
     * Example: 'some-job' => 'App\Jobs\SomeJob',
     */
    'aliases' => [
        // 'some-job' => 'App\Jobs\SomeJob',
    ],
];
