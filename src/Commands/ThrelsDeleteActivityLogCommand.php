<?php

namespace Threls\ThrelsActivityLog\Commands;

use Illuminate\Console\Command;
use Threls\ThrelsActivityLog\Models\ActivityLog;

class ThrelsDeleteActivityLogCommand extends Command
{
    public $signature = 'activity-log:delete {--older-than-days=}';

    public $description = 'Delete activity log';

    public function handle(): int
    {
        $olderThanDays = $this->option('older-than-days');

        if ($olderThanDays) {
            $count = ActivityLog::query()
                ->where('created_at', '<=', now()->subDays((int) $olderThanDays))
                ->delete();

            $this->info("Deleted {$count} activity log records older than {$olderThanDays} days.");

            return self::SUCCESS;
        }

        $retentionDays = config('activity-log.retention_days');

        if (! $retentionDays) {
            $this->error('Please provide --older-than-days or configure retention_days.');

            return self::FAILURE;
        }

        $count = (new ActivityLog)->prunable()->delete();

        $this->info("Deleted {$count} activity log records using the retention period of {$retentionDays} days.");

        return self::SUCCESS;
    }
}
