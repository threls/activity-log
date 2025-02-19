<?php

namespace Threls\ThrelsActivityLog\Commands;

use Illuminate\Console\Command;
use Threls\ThrelsActivityLog\Models\ActivityLog;

class ThrelsDeleteActivityLogCommand extends Command
{
    public $signature = 'activity-log:delete';

    public $description = 'Delete activity log';

    public function handle(): int
    {
        ActivityLog::query()->delete();

        $this->comment('Cleared activity log table.');

        return self::SUCCESS;
    }
}
