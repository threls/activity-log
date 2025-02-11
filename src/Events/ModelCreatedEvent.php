<?php

namespace Threls\ThrelsActivityLog\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Threls\ThrelsActivityLog\Data\ActivityLogData;

class ModelCreatedEvent
{
    use Dispatchable;

    public function __construct(public readonly ActivityLogData $activityLogData, public readonly Model $model) {}
}
