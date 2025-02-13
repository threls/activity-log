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
    public static function createLogObject(Model $model, ?array $oldData, ActivityLogTypeEnum $logType): ?ActivityLogData
    {
        $data = match ($logType) {
            ActivityLogTypeEnum::CREATE, ActivityLogTypeEnum::UPDATE => new ModelLogData(oldContent: $oldData, newContent: $model->toArray()),
            ActivityLogTypeEnum::DELETE => new ModelLogData(oldContent: $model->toArray(), newContent: null),
            default => null,
        };

        $tableName = $model->getTable();
        $userId = auth()->id();
        $agent = new Agent;
        $userAgent = request()->userAgent();

        return ActivityLogData::fromArray([
            'user_id' => $userId,
            'table_name' => $tableName,
            'type' => $logType->value,
            'data' => $data,
            'dirty_keys' => array_keys($model->getChanges()),
            'browser_name' => $agent->browser($userAgent),
            'platform' => $agent->platform($userAgent),
            'device' => $agent->device($userAgent),
            'ip' => request()->ip(),
            'log_date' => now(),
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
                $object = self::createLogObject($model, $model->getRawOriginal(), ActivityLogTypeEnum::UPDATE);
                event(new ModelUpdatedEvent($object, $model));
            });
        }

        if (config('activity-log.log_events.on_delete', false)) {
            self::deleted(function ($model) {
                $object = self::createLogObject($model, null, ActivityLogTypeEnum::DELETE);
                event(new ModelDeletedEvent($object, $model));
            });
        }

        if (config('activity-log.log_events.on_create', false)) {
            self::created(function ($model) {
                $object = self::createLogObject($model, null,ActivityLogTypeEnum::CREATE);
                event(new ModelCreatedEvent($object, $model));
            });
        }
    }
}
