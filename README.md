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

    'user_model' => '\App\Models\User',

    'log_pagination' => 20,

    'api_route_middleware' => 'auth:sanctum',

];
```

## Usage

Use trait `LogsActivity` on every model you want to log crud events.

```
class User extends Authenticatable
{
    use LogsActivity;

}
```

# Clear logs

```
 php artisan activity-log:delete
```

# Built in API to get logs list

```
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
