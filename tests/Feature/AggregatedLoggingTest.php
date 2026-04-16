<?php

namespace Threls\ThrelsActivityLog\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Threls\ThrelsActivityLog\Contracts\ActivityLogContract;
use Threls\ThrelsActivityLog\Enums\ActivityLogTypeEnum;
use Threls\ThrelsActivityLog\Models\ActivityLog;
use Threls\ThrelsActivityLog\Tests\TestCase;
use Threls\ThrelsActivityLog\ThrelsActivityLog;
use Threls\ThrelsActivityLog\Traits\LogsActivity;

class Survey extends Model implements ActivityLogContract
{
    use LogsActivity;

    protected $table = 'surveys';

    protected $guarded = [];

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function getLogAttributes(): array|string|null
    {
        return ['title'];
    }

    public function getIgnoreAttributes(): array|string|null
    {
        return null;
    }

    public function getLogIdentifier(): ?string
    {
        return 'title';
    }

    public function getActivityLogDescription(ActivityLogTypeEnum $type): ?string
    {
        return null;
    }

    public function getLogParent(): ?Model
    {
        return null;
    }
}

class Section extends Model implements ActivityLogContract
{
    use LogsActivity;

    protected $table = 'sections';

    protected $guarded = [];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function getLogAttributes(): array|string|null
    {
        return ['title'];
    }

    public function getIgnoreAttributes(): array|string|null
    {
        return null;
    }

    public function getLogIdentifier(): ?string
    {
        return 'title';
    }

    public function getActivityLogDescription(ActivityLogTypeEnum $type): ?string
    {
        return null;
    }

    public function getLogParent(): ?Model
    {
        return $this->survey;
    }
}

class Question extends Model implements ActivityLogContract
{
    use LogsActivity;

    protected $table = 'questions';

    protected $guarded = [];

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function getLogAttributes(): array|string|null
    {
        return ['content'];
    }

    public function getIgnoreAttributes(): array|string|null
    {
        return null;
    }

    public function getLogIdentifier(): ?string
    {
        return 'content';
    }

    public function getActivityLogDescription(ActivityLogTypeEnum $type): ?string
    {
        return null;
    }

    public function getLogParent(): ?Model
    {
        return $this->section;
    }
}

class AggregatedLoggingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained();
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained();
            $table->string('content');
            $table->timestamps();
        });
    }

    public function test_it_aggregates_multiple_model_changes_into_one_log()
    {
        ThrelsActivityLog::aggregate(function () {
            $survey = Survey::create(['title' => 'Main Survey']);

            $section = $survey->sections()->create(['title' => 'Section 1']);

            $section->questions()->create(['content' => 'Question 1.1']);
            $section->questions()->create(['content' => 'Question 1.2']);
        });

        $log = ActivityLog::first();
        expect($log->model_type)->toBe(Survey::class)
            ->and($log->data['new']['title'])->toBe('Main Survey');

        // Check nested structure
        expect($log->relations)->toHaveKey('sections');
        expect($log->relations['sections'])->toHaveCount(1);

        $sectionLog = $log->relations['sections'][0];
        expect($sectionLog['data']['new']['title'])->toBe('Section 1');

        expect($sectionLog['relations'])->toHaveKey('questions');
        expect($sectionLog['relations']['questions'])->toHaveCount(2);

        expect($sectionLog['relations']['questions'][0]['data']['new']['content'])->toBe('Question 1.1');
        expect($sectionLog['relations']['questions'][1]['data']['new']['content'])->toBe('Question 1.2');
    }
}
