<?php

namespace Threls\ThrelsActivityLog\Data;

class ModelLogData
{
    public function __construct(
        public readonly ?array $old,
        public readonly ?array $new,
    ) {}
}
