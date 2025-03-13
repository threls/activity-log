<?php

namespace Threls\ThrelsActivityLog;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Threls\ThrelsActivityLog\Commands\ThrelsDeleteActivityLogCommand;
use Threls\ThrelsActivityLog\Providers\ThrelsActivityLogEventServiceProvider;

class ThrelsActivityLogServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('activity-log')
            ->hasConfigFile()
            ->hasMigrations(['create_activity_log_table', 'update_activity_log_table'])
            ->hasCommand(ThrelsDeleteActivityLogCommand::class)
            ->hasRoute('api');
    }

    public function register()
    {
        parent::register();

        $this->app->register(ThrelsActivityLogEventServiceProvider::class);
    }
}
