<?php

// config for Threls/ThrelsActivityLog
return [

    'enabled' => env('ACTIVITY_LOG_ENABLED', true),

    'log_events' => [
        'on_create' => true,
        'on_update' => true,
        'on_delete' => true,
        'on_login' => true,
    ],

    'user_model' => '\App\Models\User',

    'log_pagination' => 20,

    'api_route_middleware' => 'auth:sanctum',

];
