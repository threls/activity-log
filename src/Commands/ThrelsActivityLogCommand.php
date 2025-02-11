<?php

namespace Threls\ThrelsActivityLog\Commands;

use Illuminate\Console\Command;

class ThrelsActivityLogCommand extends Command
{
    public $signature = 'activity-log';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
