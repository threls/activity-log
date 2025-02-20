<?php

namespace Threls\ThrelsActivityLog\Commands;

use Illuminate\Console\Command;
use Threls\ThrelsActivityLog\Models\ActivityLog;

class ThrelsDeleteActivityLogCommand extends Command
{
    public $signature = 'activity-log:delete {--older-than-months=}';

    public $description = 'Delete activity log';

    public function handle(): int
    {
        $olderThanMonths = $this->option('older-than-months');
        ActivityLog::query()
            ->when($olderThanMonths, function ($query) use ($olderThanMonths) {
                $query->where('created_at', '<=', now()->subMonths((int) $olderThanMonths));
            })
            ->delete();

        $this->comment('Cleared activity log table.');

        return self::SUCCESS;
    }
}
