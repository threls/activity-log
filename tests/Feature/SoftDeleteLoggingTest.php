<?php

namespace Threls\ThrelsActivityLog\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Threls\ThrelsActivityLog\Models\ActivityLog;
use Threls\ThrelsActivityLog\Tests\TestCase;
use Threls\ThrelsActivityLog\Traits\LogsActivity;

class SoftDeleteLoggingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('soft_delete_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->softDeletes();
            $table->timestamps();
        });

        config(['activity-log.log_events.on_update' => true]);
    }

    public function test_it_does_not_log_deleted_at_when_it_remains_null()
    {
        config(['activity-log.log_events.on_create' => true]);
        $model = SoftDeleteModel::create(['name' => 'Initial Name']);

        $model->update(['name' => 'Updated Name']);

        $log = ActivityLog::where('type', 'update')->latest()->first();

        $this->assertNotNull($log);
        $this->assertEquals('update', $log->type);
        $this->assertArrayHasKey('new', $log->data);

        // This should pass if everything is working correctly
        $this->assertArrayNotHasKey('deleted_at', $log->data['new'], 'deleted_at was logged as dirty but should have been null in both old and new data');
    }

    public function test_it_logs_deleted_at_when_it_is_changed()
    {
        config(['activity-log.log_events.on_create' => true]);
        config(['activity-log.log_events.on_delete' => true]);
        $model = SoftDeleteModel::create(['name' => 'Initial Name']);

        $model->delete();

        $log = ActivityLog::where('type', 'delete')->latest()->first();

        $this->assertNotNull($log);
        $this->assertEquals('delete', $log->type);
        // On delete, we log the old data.
        $this->assertArrayHasKey('deleted_at', $log->data['old']);
    }
}

class SoftDeleteModel extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = ['name'];

    protected $table = 'soft_delete_models';
}
