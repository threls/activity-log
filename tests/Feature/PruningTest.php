<?php

namespace Threls\ThrelsActivityLog\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Threls\ThrelsActivityLog\Models\ActivityLog;
use Threls\ThrelsActivityLog\Tests\TestCase;

class PruningTest extends TestCase
{
    public function test_it_prunes_old_logs()
    {
        // Create an old log
        $oldLog = ActivityLog::create([
            'type' => 'create',
            'data' => [],
            'browser_name' => 'Chrome',
            'platform' => 'Mac',
            'device' => 'Desktop',
            'ip' => '127.0.0.1',
        ]);
        $oldLog->created_at = now()->subDays(400);
        $oldLog->save();

        // Create a new log
        ActivityLog::create([
            'type' => 'create',
            'data' => [],
            'browser_name' => 'Chrome',
            'platform' => 'Mac',
            'device' => 'Desktop',
            'ip' => '127.0.0.1',
        ]);

        config(['activity-log.retention_days' => 365]);

        expect(ActivityLog::count())->toBe(2);

        Artisan::call('model:prune', [
            '--model' => [ActivityLog::class],
        ]);

        expect(ActivityLog::count())->toBe(1)
            ->and(ActivityLog::find($oldLog->id))->toBeNull();
    }

    public function test_it_deletes_via_command()
    {
        // Create an old log
        $oldLog = ActivityLog::create([
            'type' => 'create',
            'data' => [],
            'browser_name' => 'Chrome',
            'platform' => 'Mac',
            'device' => 'Desktop',
            'ip' => '127.0.0.1',
        ]);
        $oldLog->created_at = now()->subDays(100);
        $oldLog->save();

        ActivityLog::create([
            'type' => 'create',
            'data' => [],
            'browser_name' => 'Chrome',
            'platform' => 'Mac',
            'device' => 'Desktop',
            'ip' => '127.0.0.1',
        ]);

        // Test with option
        Artisan::call('activity-log:delete', ['--older-than-days' => 50]);
        expect(ActivityLog::count())->toBe(1);

        // Reset and test with config
        $oldLog = ActivityLog::create([
            'type' => 'create',
            'data' => [],
            'browser_name' => 'Chrome',
            'platform' => 'Mac',
            'device' => 'Desktop',
            'ip' => '127.0.0.1',
        ]);
        $oldLog->created_at = now()->subDays(100);
        $oldLog->save();

        config(['activity-log.retention_days' => 50]);
        Artisan::call('activity-log:delete');
        expect(ActivityLog::count())->toBe(1);
    }
}
