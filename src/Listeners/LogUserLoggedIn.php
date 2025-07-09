<?php

namespace Threls\ThrelsActivityLog\Listeners;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Threls\ThrelsActivityLog\Actions\CreateActivityLogAction;
use Threls\ThrelsActivityLog\Events\UserLoggedInEvent;

class LogUserLoggedIn implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct() {}

    public function handle(UserLoggedInEvent $event): void
    {
        app(CreateActivityLogAction::class)->execute($event->data);
    }
}
