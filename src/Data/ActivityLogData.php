<?php

namespace Threls\ThrelsActivityLog\Data;

class ActivityLogData
{
    public function __construct(
        public readonly string $user_id,
        public readonly string $table_name,
        public readonly string $type,
        public readonly ModelLogData $data,
        public readonly array $dirty_keys,
        public readonly string $browser_name,
        public readonly string $platform,
        public readonly string $ip,

    ) {}

    public static function fromArray(array $attributes): self
    {
        return new self(
            $attributes['user_id'],
            $attributes['table_name'],
            $attributes['type'],
            $attributes['data'],
            $attributes['dirty_keys'],
            $attributes['browser_name'],
            $attributes['platform'],
            $attributes['ip']
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->user_id,
            'table_name' => $this->table_name,
            'type' => $this->type,
            'data' => $this->data,
            'dirty_keys' => $this->dirty_keys,
            'browser_name' => $this->browser_name,
            'platform' => $this->platform,
            'ip' => $this->ip,
        ];
    }
}
