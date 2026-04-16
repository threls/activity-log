<?php

namespace Threls\ThrelsActivityLog;

use Closure;

class ThrelsActivityLog
{
    public static function aggregate(Closure $callback): void
    {
        app(ActivityLogManager::class)->aggregate($callback);
    }
}
