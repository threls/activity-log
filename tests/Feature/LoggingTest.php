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

class TestModel extends Model implements ActivityLogContract
{
    use LogsActivity;

    protected $table = 'test_models';

    protected $guarded = [];

    public ?array $ignoreAttributes = null;

    public ?array $logAttributes = null;

    public ?bool $logOnlyDirty = null;

    public ?string $logIdentifier = null;

    public ?string $customDescriptionText = null;

    public function getLogAttributes(): array|string|null
    {
        return $this->logAttributes;
    }

    public function getIgnoreAttributes(): array|string|null
    {
        return $this->ignoreAttributes;
    }

    public function getLogIdentifier(): ?string
    {
        return $this->logIdentifier;
    }

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    public function getActivityLogDescription(ActivityLogTypeEnum $logType): ?string
    {
        return $this->customDescriptionText;
    }
}

class LoggingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('test_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    public function test_it_does_not_log_unchanged_json_columns()
    {
        $model = TestModel::create([
            'name' => 'JSON Test',
            'settings' => ['theme' => 'dark', 'notifications' => true, 'tags' => ['a', 'b']],
        ]);

        ActivityLog::truncate();
        $model->logOnlyDirty = true;

        // Update with same data but different key order in root
        $model->update([
            'settings' => ['notifications' => true, 'theme' => 'dark', 'tags' => ['a', 'b']],
        ]);

        expect(ActivityLog::count())->toBe(0);

        // Update with same data as a JSON string (if someone passes it like that)
        $model->update([
            'settings' => json_encode(['notifications' => true, 'theme' => 'dark', 'tags' => ['a', 'b']]),
        ]);
        expect(ActivityLog::count())->toBe(0);

        // Update with different data in nested array
        $model->update([
            'settings' => ['notifications' => true, 'theme' => 'dark', 'tags' => ['b', 'a']],
        ]);
        expect(ActivityLog::count())->toBe(1);
    }

    public function test_it_respects_ignored_attributes_on_model()
    {
        $model = new TestModel;
        $model->ignoreAttributes = ['password'];
        $model->fill([
            'name' => 'John',
            'email' => 'john@example.com',
            'password' => 'secret',
        ])->save();

        $log = ActivityLog::first();
        expect($log->data['new'])->not->toHaveKey('password')
            ->and($log->data['new'])->toHaveKey('name');
    }

    public function test_it_respects_log_attributes_on_model()
    {
        $model = new TestModel;
        $model->logAttributes = ['name'];
        $model->fill(['name' => 'John', 'email' => 'john@example.com'])->save();

        $log = ActivityLog::first();
        expect($log->data['new'])->toHaveKey('name')
            ->and($log->data['new'])->not->toHaveKey('email');
    }

    public function test_it_respects_log_only_dirty_on_model()
    {
        $model = TestModel::create(['name' => 'John', 'email' => 'john@example.com']);
        ActivityLog::truncate();

        $model->logOnlyDirty = true;

        // Update non-tracked field
        $model->update(['name' => 'John Doe']);

        $log = ActivityLog::first();
        expect($log->type)->toBe(ActivityLogTypeEnum::UPDATE->value)
            ->and($log->data['new'])->toBe(['name' => 'John Doe'])
            ->and($log->data['old'])->toBe(['name' => 'John']);

        ActivityLog::truncate();

        // Update with same data
        $model->update(['name' => 'John Doe']);
        expect(ActivityLog::count())->toBe(0);
    }

    public function test_it_works_in_cli_context()
    {
        $model = TestModel::create(['name' => 'CLI User']);

        $log = ActivityLog::first();
        expect($log->browser_name)->toBeString()
            ->and($log->ip)->toBeString();
    }

    public function test_it_provides_helpful_scopes()
    {
        $model1 = TestModel::create(['name' => 'Model 1']);
        $model2 = TestModel::create(['name' => 'Model 2']);

        expect(ActivityLog::forModel($model1)->count())->toBe(1)
            ->and(ActivityLog::forModel($model2)->count())->toBe(1)
            ->and(ActivityLog::ofType(ActivityLogTypeEnum::CREATE)->count())->toBe(2);
    }

    public function test_it_builds_description_with_default_identifier()
    {
        $user = \Illuminate\Support\Facades\DB::table('users')->insertGetId([
            'name' => 'Sabina',
            'email' => 'sabina@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        auth()->loginUsingId($user);

        $model = TestModel::create(['name' => 'Test Item']);
        $log = ActivityLog::first();
        expect($log->description)->toBe("Sabina created TestModel '{$model->id}'");
    }

    public function test_it_respects_log_identifier_on_model()
    {
        $user = \Illuminate\Support\Facades\DB::table('users')->insertGetId([
            'name' => 'Sabina',
            'email' => 'sabina@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        auth()->loginUsingId($user);

        $model = new TestModel;
        $model->logIdentifier = 'name';
        $model->fill(['name' => 'Custom Name'])->save();

        $log = ActivityLog::first();
        expect($log->description)->toBe("Sabina created TestModel 'Custom Name'");
    }

    public function test_it_respects_custom_description_method()
    {
        $model = new TestModel;
        $model->fill(['name' => 'Special Item']);
        $model->customDescriptionText = 'Custom description for Special Item';
        $model->save();

        $log = ActivityLog::first();
        expect($log->description)->toBe('Custom description for Special Item');
    }
}
