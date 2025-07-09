<?php

namespace Threls\ThrelsActivityLog\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Jenssegers\Agent\Agent;
use Threls\ThrelsActivityLog\Data\ActivityLogData;
use Threls\ThrelsActivityLog\Data\ModelLogData;
use Threls\ThrelsActivityLog\Enums\ActivityLogTypeEnum;

class UserLoggedInEvent
{
    use Dispatchable;

    public ActivityLogData $data;

    public function __construct(public readonly Model $model)
    {

        $tableName = $model->getTable();
        $userId = $model->id;
        $agent = new Agent;
        $userAgent = request()->userAgent();

        $this->data = ActivityLogData::fromArray([
            'user_id' => $userId,
            'model_id' => $model->id,
            'model_type' => get_class($model),
            'table_name' => $tableName,
            'type' => ActivityLogTypeEnum::LOGIN,
            'data' => new ModelLogData(oldContent: $model->toArray(), newContent: $model->toArray()),
            'dirty_keys' => null,
            'browser_name' => $agent->browser($userAgent),
            'platform' => $agent->platform($userAgent),
            'device' => $agent->device($userAgent),
            'ip' => request()->ip(),
            'log_date' => now(),
        ]);
    }
}
