<?php

namespace Threls\ThrelsActivityLog\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Threls\ThrelsActivityLog\Contracts\ActivityLogContract;
use Threls\ThrelsActivityLog\Enums\ActivityLogTypeEnum;
use Threls\ThrelsActivityLog\Models\ActivityLog;
use Threls\ThrelsActivityLog\Tests\TestCase;
use Threls\ThrelsActivityLog\Traits\LogsActivity;

class DateLoggingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('date_test_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('onboarding_date')->nullable();
            $table->dateTime('onboarding_datetime')->nullable();
            $table->timestamps();
        });
    }

    public function test_it_does_not_log_unchanged_date_columns()
    {
        $model = new DateTestModel;
        $model->name = 'Test';
        $model->onboarding_date = '2026-02-28';
        $model->save();

        ActivityLog::query()->delete();

        $model->onboarding_date = '2026-02-28';
        $model->save();

        $this->assertEquals(0, ActivityLog::count(), 'Activity log was created even though date was not changed');

        // Force an update with the same date but in a different format/type if possible
        $model->onboarding_date = \Carbon\Carbon::parse('2026-02-28')->startOfDay();
        $model->save();

        $this->assertEquals(0, ActivityLog::count(), 'Activity log was created even though date was same but different type');
    }

    public function test_it_does_not_log_unchanged_datetime_columns()
    {
        $model = new DateTestModel;
        $model->name = 'Test';
        $model->onboarding_datetime = '2026-02-27 23:00:00';
        $model->save();

        ActivityLog::query()->delete();

        $model->onboarding_datetime = '2026-02-27 23:00:00';
        $model->save();

        $this->assertEquals(0, ActivityLog::count(), 'Activity log was created even though datetime was not changed');

        // Force update with Carbon object
        $model->onboarding_datetime = \Carbon\Carbon::parse('2026-02-27 23:00:00');
        $model->save();

        $this->assertEquals(0, ActivityLog::count(), 'Activity log was created even though datetime was same but Carbon object');
    }

    public function test_it_logs_date_correctly_on_update()
    {
        $model = new DateTestModel;
        $model->name = 'Date Test';
        $model->onboarding_date = '2026-02-28';
        $model->save();

        ActivityLog::truncate();

        $model->update([
            'onboarding_date' => '2026-02-26',
        ]);

        $log = ActivityLog::first();
        expect($log)->not->toBeNull();

        $data = $log->data;
        // The issue states that new onboarding_date is saved as datetime string instead of date string
        // {"new": {"onboarding_date": "2026-02-26T23:00:00.000000Z"}, "old": {"onboarding_date": "2026-02-28"}}

        expect($data['new']['onboarding_date'])->toBe('2026-02-26');
        expect($data['old']['onboarding_date'])->toBe('2026-02-28');
    }

    public function test_it_logs_datetime_correctly_on_update()
    {
        $model = new DateTestModel;
        $model->name = 'DateTime Test';
        $model->onboarding_datetime = '2026-02-27 23:00:00';
        $model->save();

        ActivityLog::truncate();

        $model->update([
            'onboarding_datetime' => '2026-02-28 10:00:00',
        ]);

        $log = ActivityLog::first();
        expect($log)->not->toBeNull();

        $data = $log->data;
        expect($data['new']['onboarding_datetime'])->toBe('2026-02-28 10:00:00');
        expect($data['old']['onboarding_datetime'])->toBe('2026-02-27 23:00:00');
    }
}

class DateTestModel extends Model implements ActivityLogContract
{
    use LogsActivity;

    protected $fillable = ['name', 'onboarding_date', 'onboarding_datetime'];

    public $logOnlyDirty = true;

    protected function casts(): array
    {
        return [
            'onboarding_date' => 'date',
            'onboarding_datetime' => 'datetime',
        ];
    }

    public function getLogAttributes(): array|string|null
    {
        return ['name', 'onboarding_date', 'onboarding_datetime'];
    }

    public function getIgnoreAttributes(): array|string|null
    {
        return null;
    }

    public function getLogIdentifier(): ?string
    {
        return 'name';
    }

    public function getActivityLogDescription(ActivityLogTypeEnum $logType): ?string
    {
        return null;
    }

    public function getLogParent(): ?Model
    {
        return null;
    }
}
