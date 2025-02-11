<?php

namespace Threls\ThrelsActivityLog\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Threls\ThrelsActivityLog\ThrelsActivityLog
 */
class ThrelsActivityLog extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Threls\ThrelsActivityLog\ThrelsActivityLog::class;
    }
}
