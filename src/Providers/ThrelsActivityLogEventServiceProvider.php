<?php

namespace Threls\ThrelsActivityLog\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Threls\ThrelsActivityLog\Events\ModelCreatedEvent;
use Threls\ThrelsActivityLog\Events\ModelDeletedEvent;
use Threls\ThrelsActivityLog\Events\ModelUpdatedEvent;
use Threls\ThrelsActivityLog\Listeners\LogModelCreated;
use Threls\ThrelsActivityLog\Listeners\LogModelDeleted;
use Threls\ThrelsActivityLog\Listeners\LogModelUpdated;

class ThrelsActivityLogEventServiceProvider extends ServiceProvider
{
    protected $listen = [
        ModelCreatedEvent::class => [
            LogModelCreated::class,
        ],
        ModelUpdatedEvent::class => [
            LogModelUpdated::class,
        ],
        ModelDeletedEvent::class => [
            LogModelDeleted::class,
        ],

    ];

    public function boot()
    {
        parent::boot();
    }
}
