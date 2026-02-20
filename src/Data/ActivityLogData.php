<?php

namespace Threls\ThrelsActivityLog\Data;

use Carbon\Carbon;

class ActivityLogData
{
    public function __construct(
        public readonly ?string $user_id,
        public readonly ?string $model_id,
        public readonly ?string $model_type,
        public readonly string $table_name,
        public readonly string $type,
        public readonly ?string $description,
        public readonly ModelLogData $data,
        public readonly array $dirty_keys,
        public readonly string $browser_name,
        public readonly string $platform,
        public readonly string $device,
        public readonly string $ip,
        public Carbon $log_date

    ) {}

    public static function fromArray(array $attributes): self
    {
        $data = $attributes['data'];
        if (is_array($data) && (isset($data['old']) || isset($data['new']))) {
            $data = new ModelLogData($data['old'] ?? null, $data['new'] ?? null);
        }

        return new self(
            $attributes['user_id'],
            $attributes['model_id'],
            $attributes['model_type'],
            $attributes['table_name'],
            $attributes['type'],
            $attributes['description'] ?? null,
            $data,
            $attributes['dirty_keys'],
            $attributes['browser_name'],
            $attributes['platform'],
            $attributes['device'] ?? 'unknown',
            $attributes['ip'],
            Carbon::parse($attributes['log_date']),
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->user_id,
            'model_id' => $this->model_id,
            'model_type' => $this->model_type,
            'table_name' => $this->table_name,
            'type' => $this->type,
            'description' => $this->description,
            'data' => [
                'old' => $this->data->old,
                'new' => $this->data->new,
            ],
            'dirty_keys' => $this->dirty_keys,
            'browser_name' => $this->browser_name,
            'platform' => $this->platform,
            'device' => $this->device,
            'ip' => $this->ip,
            'log_date' => $this->log_date->toDateTimeString(),
        ];
    }
}
