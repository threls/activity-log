<?php

namespace Threls\ThrelsActivityLog\Contracts;

interface ActivityLogContract
{
    public function getActivityLogDisplayName(): string;

    public function getCreateActivityDescription(): ?string;

    public function getUpdateActivityDescription(): ?string;

    public function getDeleteActivityDescription(): ?string;
}
