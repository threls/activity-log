<?php

namespace Threls\ThrelsActivityLog\Contracts;

interface HasActivityLogDisplayName
{
    public function getActivityLogDisplayName(): string;
}