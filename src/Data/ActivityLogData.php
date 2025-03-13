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
        public readonly ModelLogData $data,
        public readonly array $dirty_keys,
        public readonly string $browser_name,
        public readonly string $platform,
        public readonly string $ip,
        public Carbon $log_date

    ) {}

    public static function fromArray(array $attributes): self
    {
        return new self(
            $attributes['user_id'],
            $attributes['model_id'],
            $attributes['model_type'],
            $attributes['table_name'],
            $attributes['type'],
            $attributes['data'],
            $attributes['dirty_keys'],
            $attributes['browser_name'],
            $attributes['platform'],
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
            'data' => $this->data,
            'dirty_keys' => $this->dirty_keys,
            'browser_name' => $this->browser_name,
            'platform' => $this->platform,
            'ip' => $this->ip,
            'log_date' => $this->log_date->toDateTimeString(),
        ];
    }
}
