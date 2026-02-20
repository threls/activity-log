<?php

namespace Threls\ThrelsActivityLog\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use Threls\ThrelsActivityLog\ThrelsActivityLogServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Threls\\ThrelsActivityLog\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            ThrelsActivityLogServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('queue.default', 'sync');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->timestamps();
        });

        $migrations = [
            include __DIR__.'/../database/migrations/2025_02_11_164955_create_activity_log_table.php',
            include __DIR__.'/../database/migrations/2025_03_13_142844_update_activity_log.php',
            include __DIR__.'/../database/migrations/2025_03_17_000001_add_description_to_activity_log.php',
        ];

        foreach ($migrations as $migration) {
            $migration->up();
        }
    }
}
