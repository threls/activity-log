<?php

namespace Threls\ThrelsActivityLog\Models;

use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Threls\ThrelsActivityLog\Contracts\ActivityLogContract;
use Threls\ThrelsActivityLog\Enums\ActivityLogTypeEnum;

class ActivityLog extends Model
{
    use MassPrunable;

    protected $table = 'activity_log';

    protected $guarded = ['id'];

    protected $casts = [
        'data' => 'array',
        'dirty_keys' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('activity-log.user_model', 'App\Models\User'));
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForModel($query, Model $model)
    {
        return $query->where([
            'model_id' => $model->getKey(),
            'model_type' => $model->getMorphClass(),
        ]);
    }

    public function prunable()
    {
        $days = config('activity-log.retention_days');

        if (! $days) {
            return $this->whereRaw('1 = 0');
        }

        return $this->where('created_at', '<=', now()->subDays($days));
    }

    public function scopeOfType($query, string|ActivityLogTypeEnum $type)
    {
        return $query->where('type', $type instanceof ActivityLogTypeEnum ? $type->value : $type);
    }

    public function scopeForUser($query, $user)
    {
        return $query->where('user_id', $user instanceof Model ? $user->getKey() : $user);
    }

    public function getModelDisplayName(): string
    {
        if ($this->model instanceof ActivityLogContract) {
            $identifierColumn = $this->model->getLogIdentifier();
            if ($identifierColumn && isset($this->model->{$identifierColumn})) {
                return (string) $this->model->{$identifierColumn};
            }
        }

        return class_basename($this->model_type)." #{$this->model_id}";
    }
}
