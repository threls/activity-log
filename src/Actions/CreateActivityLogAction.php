<?php

namespace Threls\ThrelsActivityLog\Actions;

use Threls\ThrelsActivityLog\Data\ActivityLogData;
use Threls\ThrelsActivityLog\Models\ActivityLog;

class CreateActivityLogAction
{
    public function execute(ActivityLogData $activityLogData): ActivityLog
    {
        return ActivityLog::create($activityLogData->toArray());
    }
}
