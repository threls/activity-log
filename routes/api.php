<?php

use Threls\ThrelsActivityLog\Controllers\ActivityLogController;

Route::group(['middleware' => 'auth'], function () {});

Route::group(['middleware' => config('activity-log.api_route_middleware')], function () {
    Route::prefix('threls-activity-log')->group(function () {
        Route::get('logs', ActivityLogController::class);
    });

});
