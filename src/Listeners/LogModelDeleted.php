<?php

namespace Threls\ThrelsActivityLog\Listeners;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Threls\ThrelsActivityLog\Actions\CreateActivityLogAction;
use Threls\ThrelsActivityLog\Events\ModelDeletedEvent;

class LogModelDeleted implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct() {}

    public function handle(ModelDeletedEvent $event): void
    {
        app(CreateActivityLogAction::class)->execute($event->activityLogData);
    }
}
