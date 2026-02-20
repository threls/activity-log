<?php

namespace Threls\ThrelsActivityLog\Contracts;

use Threls\ThrelsActivityLog\Enums\ActivityLogTypeEnum;

interface ActivityLogContract
{
    public function getLogAttributes(): array|string|null;

    public function getIgnoreAttributes(): array|string|null;

    public function getLogIdentifier(): ?string;

    public function getActivityLogDescription(ActivityLogTypeEnum $type): ?string;
}
