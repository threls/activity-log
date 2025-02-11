<?php

namespace Threls\ThrelsActivityLog;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Threls\ThrelsActivityLog\Commands\ThrelsActivityLogCommand;
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
            // ->hasViews()
            ->hasMigration('create_activity_log_table')
            ->hasCommand(ThrelsActivityLogCommand::class)
            ->hasRoute('api');
    }

    public function register()
    {
        parent::register();

        $this->app->register(ThrelsActivityLogEventServiceProvider::class);
    }
}
