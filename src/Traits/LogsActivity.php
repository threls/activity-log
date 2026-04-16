<?php

namespace Threls\ThrelsActivityLog\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Jenssegers\Agent\Agent;
use Threls\ThrelsActivityLog\ActivityLogManager;
use Threls\ThrelsActivityLog\Contracts\ActivityLogContract;
use Threls\ThrelsActivityLog\Data\ActivityLogData;
use Threls\ThrelsActivityLog\Data\ModelLogData;
use Threls\ThrelsActivityLog\Enums\ActivityLogTypeEnum;
use Threls\ThrelsActivityLog\Events\ModelCreatedEvent;
use Threls\ThrelsActivityLog\Events\ModelDeletedEvent;
use Threls\ThrelsActivityLog\Events\ModelUpdatedEvent;
use Threls\ThrelsActivityLog\Models\ActivityLog;

trait LogsActivity
{
    public static function createLogObject(Model $model, ?array $oldData, ActivityLogTypeEnum $logType): ?ActivityLogData
    {
        $modelArray = self::getFilteredAttributes($model, $model->toArray());
        $oldData = $oldData !== null ? self::getFilteredAttributes($model, $oldData) : null;

        $dirtyKeys = [];
        if ($logType === ActivityLogTypeEnum::UPDATE) {
            $diff = self::computeDirtyDiff($modelArray, $oldData ?? [], $model);
            if ($diff === null) {
                return null;
            }

            $dirtyKeys = array_keys($diff['new'] ?? []);

            if (self::resolveLogOnlyDirty($model)) {
                $modelArray = $diff['new'];
                $oldData = $diff['old'];
            }
        }

        $data = match ($logType) {
            ActivityLogTypeEnum::CREATE, ActivityLogTypeEnum::UPDATE => new ModelLogData(old: $oldData, new: $modelArray),
            ActivityLogTypeEnum::DELETE => new ModelLogData(old: $modelArray, new: null),
            default => null,
        };

        $userId = self::resolveUserId();
        $causerName = self::resolveCauserName($userId);

        $agent = new Agent;
        $userAgent = request()?->userAgent();

        return ActivityLogData::fromArray([
            'user_id' => $userId,
            'model_id' => $model->id,
            'model_type' => $model::class,
            'table_name' => $model->getTable(),
            'type' => $logType->value,
            'description' => self::resolveDescription($model, $logType, $causerName),
            'data' => $data,
            'dirty_keys' => $dirtyKeys,
            'browser_name' => $userAgent ? $agent->browser($userAgent) : 'unknown',
            'platform' => $userAgent ? $agent->platform($userAgent) : 'unknown',
            'device' => $userAgent ? $agent->device($userAgent) : 'unknown',
            'ip' => request()?->ip() ?? '127.0.0.1',
            'log_date' => now(),
        ]);
    }

    protected static function getFilteredAttributes(Model $model, array $attributes): array
    {
        $ignoreAttributes = self::resolveIgnoreAttributes($model);
        $logAttributes = self::resolveLogAttributes($model);

        if ($logAttributes !== null) {
            $attributes = array_intersect_key($attributes, array_flip((array) $logAttributes));
        }

        $attributes = array_diff_key($attributes, array_flip((array) $ignoreAttributes));

        return self::normalizeJsonColumns($attributes, $model);
    }

    protected static function resolveIgnoreAttributes(Model $model): array
    {
        if ($model instanceof ActivityLogContract && ($attrs = $model->getIgnoreAttributes()) !== null) {
            return (array) $attrs;
        }

        return (property_exists($model, 'ignoreAttributes') && $model->ignoreAttributes !== null)
            ? (array) $model->ignoreAttributes
            : [];
    }

    protected static function resolveLogAttributes(Model $model): ?array
    {
        if ($model instanceof ActivityLogContract && ($attrs = $model->getLogAttributes()) !== null) {
            return (array) $attrs;
        }

        return (property_exists($model, 'logAttributes') && $model->logAttributes !== null)
            ? (array) $model->logAttributes
            : null;
    }

    protected static function normalizeJsonColumns(?array $data, Model $model): ?array
    {
        if ($data === null) {
            return null;
        }

        $casts = $model->getCasts();

        foreach ($data as $key => $value) {
            if (isset($casts[$key]) && ($casts[$key] === 'array' || $casts[$key] === 'json' || $casts[$key] === 'object' || str_contains($casts[$key], 'AsArrayObject') || str_contains($casts[$key], 'AsCollection'))) {
                if (is_string($value)) {
                    $decoded = json_decode($value, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $data[$key] = $decoded;
                    }
                }
            } elseif (is_string($value) && str_starts_with($value, '{') && str_ends_with($value, '}')) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $data[$key] = $decoded;
                }
            }
        }

        return $data;
    }

    protected static function resolveLogOnlyDirty(Model $model): bool
    {
        return property_exists($model, 'logOnlyDirty') && $model->logOnlyDirty !== null
            ? $model->logOnlyDirty
            : config('activity-log.log_only_dirty', false);
    }

    protected static function computeDirtyDiff(array $current, ?array $old, Model $model): ?array
    {
        $old = $old ?? [];
        unset($current['updated_at'], $current['created_at'], $old['updated_at'], $old['created_at']);

        $casts = $model->getCasts();
        $changes = [];

        foreach ($current as $key => $value) {
            if (! array_key_exists($key, $old) || self::isAttributeDifferent($value, $old[$key], $key, $casts)) {
                $changes[$key] = self::formatAttributeValue($value, $key, $casts);
            }
        }

        if (empty($changes)) {
            return null;
        }

        $oldData = [];
        foreach (array_intersect_key($old, $changes) as $key => $value) {
            $oldData[$key] = self::formatAttributeValue($value, $key, $casts);
        }

        return ['new' => $changes, 'old' => $oldData];
    }

    protected static function isAttributeDifferent(mixed $a, mixed $b, string $key, array $casts): bool
    {
        if (is_array($a) && is_array($b)) {
            if (count($a) !== count($b)) {
                return true;
            }

            ksort($a);
            ksort($b);

            foreach ($a as $k => $value) {
                if (! array_key_exists($k, $b) || self::isAttributeDifferent($value, $b[$k], $k, $casts)) {
                    return true;
                }
            }

            return false;
        }

        if (isset($casts[$key])) {
            $cast = $casts[$key];
            if ($cast === 'date' || $cast === 'datetime' || str_starts_with($cast, 'date:') || str_starts_with($cast, 'datetime:')) {
                return self::isDateDifferent($a, $b, $cast);
            }
        }

        return (is_array($a) || is_array($b)) || (string) $a !== (string) $b;
    }

    protected static function isDateDifferent(mixed $a, mixed $b, string $cast): bool
    {
        try {
            if ($a === null && $b === null) {
                return false;
            }

            if ($a === null || $b === null) {
                return true;
            }

            $tz = config('app.timezone');
            $dateA = Carbon::parse($a)->setTimezone($tz);
            $dateB = Carbon::parse($b)->setTimezone($tz);

            if ($cast === 'date' || str_starts_with($cast, 'date:')) {
                return $dateA->toDateString() !== $dateB->toDateString();
            }

            return ! $dateA->equalTo($dateB);
        } catch (\Exception) {
            return (string) $a !== (string) $b;
        }
    }

    protected static function formatAttributeValue(mixed $value, string $key, array $casts): mixed
    {
        if ($value === null || ! isset($casts[$key])) {
            return $value;
        }

        $cast = $casts[$key];
        if ($cast === 'date' || str_starts_with($cast, 'date:')) {
            return Carbon::parse($value)->toDateString();
        }

        if ($cast === 'datetime' || str_starts_with($cast, 'datetime:')) {
            $format = str_contains($cast, ':') ? explode(':', $cast)[1] : 'Y-m-d H:i:s';

            return Carbon::parse($value)->format($format);
        }

        return $value;
    }

    protected static function resolveUserId(): ?string
    {
        return auth()->id() ?? null;
    }

    protected static function resolveCauserName(?string $userId): string
    {
        if (! $userId) {
            return 'Unknown';
        }

        $nameAttr = config('activity-log.causer_name_attribute', 'name');

        if (($authUser = auth()->user()) && isset($authUser->{$nameAttr})) {
            return (string) $authUser->{$nameAttr};
        }

        $userModelClass = config('activity-log.user_model', 'App\\Models\\User');
        if (class_exists($userModelClass) && ($user = $userModelClass::find($userId)) && isset($user->{$nameAttr})) {
            return (string) $user->{$nameAttr};
        }

        if (Schema::hasTable('users') && ($record = DB::table('users')->find($userId)) && isset($record->{$nameAttr})) {
            return (string) $record->{$nameAttr};
        }

        return 'Unknown';
    }

    protected static function resolveDescription(Model $model, ActivityLogTypeEnum $logType, string $causerName): string
    {
        $customDescription = $model instanceof ActivityLogContract
            ? $model->getActivityLogDescription($logType)
            : (method_exists($model, 'getActivityLogDescription') ? $model->getActivityLogDescription($logType) : null);

        if (is_string($customDescription) && $customDescription !== '') {
            return $customDescription;
        }

        $identifierColumn = $model instanceof ActivityLogContract
            ? $model->getLogIdentifier()
            : (property_exists($model, 'logIdentifier') ? $model->logIdentifier : null);

        $identifierColumn ??= config('activity-log.default_log_identifier', 'id');

        $identifier = $model->getAttribute($identifierColumn) ?? $model->getKey();

        return "{$causerName} {$logType->getVerb()} ".class_basename($model)." '{$identifier}'";
    }

    protected static function checkLoggingIsEnabled()
    {
        return config('activity-log.enabled', true);
    }

    public static function bootLogsActivity(): void
    {
        if (! self::checkLoggingIsEnabled()) {
            return;
        }

        if (config('activity-log.log_events.on_update', false)) {
            static $oldData = [];

            self::updating(function ($model) use (&$oldData) {
                $oldData[$model->getKey()] = $model->getRawOriginal();

                if (self::resolveLogOnlyDirty($model)) {
                    self::logModelEvent($model, $oldData[$model->getKey()], ActivityLogTypeEnum::UPDATE, ModelUpdatedEvent::class);
                }
            });

            self::saved(function ($model) use (&$oldData) {
                if (! self::resolveLogOnlyDirty($model) && ! $model->wasRecentlyCreated) {
                    self::logModelEvent($model, $oldData[$model->getKey()] ?? null, ActivityLogTypeEnum::UPDATE, ModelUpdatedEvent::class);
                }

                unset($oldData[$model->getKey()]);
            });
        }

        if (config('activity-log.log_events.on_delete', false)) {
            self::deleted(fn ($model) => self::logModelEvent($model, null, ActivityLogTypeEnum::DELETE, ModelDeletedEvent::class));
        }

        if (config('activity-log.log_events.on_create', false)) {
            self::created(fn ($model) => self::logModelEvent($model, null, ActivityLogTypeEnum::CREATE, ModelCreatedEvent::class));
        }
    }

    protected static function logModelEvent(Model $model, ?array $oldData, ActivityLogTypeEnum $type, string $eventClass): void
    {
        $logObject = self::createLogObject($model, $oldData, $type);

        if ($logObject !== null) {
            $manager = app(ActivityLogManager::class);
            if ($manager->isAggregating()) {
                $manager->addLog($logObject, $model);
            } else {
                event(new $eventClass($logObject, $model));
            }
        }
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'model_id', 'id')->where(['model_type' => get_class($this)]);
    }
}
