<?php

namespace Threls\ThrelsActivityLog\Controllers;

use Threls\ThrelsActivityLog\Models\ActivityLog;

class ActivityLogController
{
    public function __invoke()
    {
        return response()
            ->json(ActivityLog::with('user')->paginate(config('activity-log.log_pagination', 20)));
    }
}
