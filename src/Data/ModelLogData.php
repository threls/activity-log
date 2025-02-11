<?php

namespace Threls\ThrelsActivityLog\Data;

class ModelLogData
{
    public function __construct(
        public readonly ?array $oldContent,
        public readonly ?array $newContent,
    ) {}
}
