<?php

namespace Threls\ThrelsActivityLog\Listeners;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Threls\ThrelsActivityLog\Actions\CreateActivityLogAction;
use Threls\ThrelsActivityLog\Events\ModelCreatedEvent;

class LogModelUpdated implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct() {}

    public function handle(ModelCreatedEvent $event): void
    {
        app(CreateActivityLogAction::class)->execute($event->activityLogData);
    }
}
