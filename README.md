# This is my package activity-log

[![Latest Version on Packagist](https://img.shields.io/packagist/v/threls/activity-log.svg?style=flat-square)](https://packagist.org/packages/threls/activity-log)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/threls/activity-log/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/threls/activity-log/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/threls/activity-log/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/threls/activity-log/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/threls/activity-log.svg?style=flat-square)](https://packagist.org/packages/threls/activity-log)

This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/activity-log.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/activity-log)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Installation

You can install the package via composer:

```bash
composer require threls/activity-log
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="activity-log-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="activity-log-config"
```

This is the contents of the published config file:

```php
return [

    'enabled' => env('ACTIVITY_LOG_ENABLED', true),

    'log_events' => [
        'on_create' => true,
        'on_update' => true,
        'on_delete' => true,
        'on_login' => true,
    ],

    // When true, updates will only log changed (dirty) attributes
    'log_only_dirty' => true,

    // The User model class used to resolve causer name/id
    'user_model' => '\App\Models\User',

    // Which attribute on the causer to display in descriptions (e.g. name/email)
    'causer_name_attribute' => 'name',

    // Global fallback identifier used in descriptions when a model doesn't override it
    'default_log_identifier' => 'id',

    // API listing pagination size
    'log_pagination' => 20,

    // Middleware used by the built-in logs API route
    'api_route_middleware' => 'auth:sanctum',

    // Number of days to retain logs before pruning (default 365)
    'retention_days' => 365,
];
```

## Usage

- Add the `LogsActivity` trait to any model you want to track.
- Implement `ActivityLogContract` to strongly define your model's logging configuration.
- By default, descriptions are standardized as: `"{causerName} {verb} {ModelName} '{identifier}'"`.

### Basic

```php
use Threls\ThrelsActivityLog\Traits\LogsActivity;

class Product extends Model
{
    use LogsActivity;
}
```

This will emit logs like: `Sabina created Product '42'` using the global `default_log_identifier`.

### Per-model configuration via properties (no interface)

```php
class Product extends Model
{
    use LogsActivity;

    public ?array $logAttributes = ['name', 'price'];
    public ?array $ignoreAttributes = ['updated_at'];
    public ?bool $logOnlyDirty = true; // only diffs on update
    public ?string $logIdentifier = 'name'; // used in description
}
```

### Strict configuration via interface

```php
use Threls\ThrelsActivityLog\Contracts\ActivityLogContract;
use Threls\ThrelsActivityLog\Enums\ActivityLogTypeEnum;

class Product extends Model implements ActivityLogContract
{
    use LogsActivity;

    public function getLogAttributes(): array|string|null { return ['name', 'price']; }
    public function getIgnoreAttributes(): array|string|null { return ['updated_at']; }
    public function getLogOnlyDirty(): ?bool { return true; }
    public function getLogIdentifier(): ?string { return 'name'; }
    public function getActivityLogDescription(ActivityLogTypeEnum $type): ?string { return null; }
    public function getLogParent(): ?Model { return null; }
}
```

### Aggregated Logging

You can group multiple logical actions into a single log entry using `ThrelsActivityLog::aggregate()`. This is useful for relationship trees (e.g., creating a Survey with its Sections and Questions).

To support this, define the hierarchy in your models by implementing `getLogParent()` from the `ActivityLogContract`.

```php
use Threls\ThrelsActivityLog\Facades\ThrelsActivityLog;

ThrelsActivityLog::aggregate(function () {
    $survey = Survey::create(['title' => 'Customer Feedback']);
    $survey->sections()->create(['title' => 'Service Quality']);
});
```

The resulting log will be associated with the root model (`Survey`) and contain all nested changes in a `relations` JSON column.

#### Defining Hierarchy

```php
class SurveySection extends Model implements ActivityLogContract
{
    use LogsActivity;

    public function survey() {
        return $this->belongsTo(Survey::class);
    }

    public function getLogParent(): ?Model {
        return $this->survey;
    }

    // ... other interface methods
}
```

### CLI / Queues safety
- The package guards request-dependent values (user agent, IP). In non-HTTP contexts, defaults like `unknown` and `127.0.0.1` are used.

### Maintenance

#### Log Pruning (Recommended)
This package supports Laravel's native model pruning. To keep your `activity_log` table small, you should schedule the `model:prune` command in your application's `routes/console.php` (or `app/Console/Kernel.php` for older Laravel versions):

```php
use Illuminate\Support\Facades\Schedule;
use Threls\ThrelsActivityLog\Models\ActivityLog;

Schedule::command('model:prune', [
    '--model' => [ActivityLog::class],
])->daily();
```

You can configure the retention period in `config/activity-log.php`:
```php
'retention_days' => 365, // Keep logs for 1 year
```

#### Manual Cleanup
Alternatively, you can manually delete old logs using the provided command:

```bash
# Deletes logs older than 30 days
php artisan activity-log:delete --older-than-days=30

# Deletes logs using the 'retention_days' from config
php artisan activity-log:delete
```

#### Built-in API to list logs
```http
GET /threls-activity-log/logs
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [sabina](https://github.com/sabina)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
