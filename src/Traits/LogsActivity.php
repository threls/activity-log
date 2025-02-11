<?php

namespace Threls\ThrelsActivityLog\Traits;

use Illuminate\Database\Eloquent\Model;
use Jenssegers\Agent\Agent;
use Threls\ThrelsActivityLog\Data\ActivityLogData;
use Threls\ThrelsActivityLog\Data\ModelLogData;
use Threls\ThrelsActivityLog\Enums\ActivityLogTypeEnum;
use Threls\ThrelsActivityLog\Events\ModelCreatedEvent;
use Threls\ThrelsActivityLog\Events\ModelDeletedEvent;
use Threls\ThrelsActivityLog\Events\ModelUpdatedEvent;

trait LogsActivity
{
    public static function createLogObject(Model $model, ActivityLogTypeEnum $logType): ?ActivityLogData
    {
        $data = match ($logType) {
            ActivityLogTypeEnum::CREATE => new ModelLogData(oldContent: null, newContent: $model->toArray()),
            ActivityLogTypeEnum::UPDATE, ActivityLogTypeEnum::DELETE => new ModelLogData(oldContent: $model->getRawOriginal(), newContent: $model->toArray()),
            default => null,
        };

        $tableName = $model->getTable();
        $userId = auth()->id();
        $agent = new Agent;
        $userAgent = request()->userAgent();

        return ActivityLogData::fromArray([
            'user_id' => $userId,
            'table_name' => $tableName,
            'type' => $logType,
            'data' => $data,
            'dirty_keys' => $model->getChanges(),
            'browser_name' => $agent->browser($userAgent),
            'platform' => $agent->platform($userAgent),
            'device' => $agent->device($userAgent),
            'ip' => request()->ip(),
        ]);
    }

    protected static function checkLoggingIsEnabled()
    {
        return config('activity-log.enabled', true);
    }

    public static function bootLogsActivity()
    {
        if (! self::checkLoggingIsEnabled()) {
            return;
        }

        if (config('activity-log.log_events.on_update', false)) {
            self::updated(function ($model) {
                $object = self::createLogObject($model, ActivityLogTypeEnum::CREATE);
                event(new ModelUpdatedEvent($object, $model));
            });
        }

        if (config('activity-log.log_events.on_delete', false)) {
            self::deleted(function ($model) {
                $object = self::createLogObject($model, ActivityLogTypeEnum::CREATE);
                event(new ModelDeletedEvent($object, $model));
            });
        }

        if (config('activity-log.log_events.on_create', false)) {
            self::created(function ($model) {
                $object = self::createLogObject($model, ActivityLogTypeEnum::CREATE);
                event(new ModelCreatedEvent($object, $model));
            });
        }
    }
}
