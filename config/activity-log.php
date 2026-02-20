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

    'log_only_dirty' => true,

    'user_model' => '\App\Models\User',

    'log_pagination' => 20,

    'api_route_middleware' => 'auth:sanctum',
    'default_log_identifier' => 'id',
    'causer_name_attribute' => 'name',
    'retention_days' => 365,
];
