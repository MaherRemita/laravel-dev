<?php

namespace maherremita\LaravelDev;

use Illuminate\Support\ServiceProvider;
use maherremita\LaravelDev\Console\commands\DevCommand;

class LaravelDevServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                DevCommand::class,
            ]);
        }

        // Publish the configuration file
        $this->publishes([
            __DIR__.'/../config/laravel_dev.php' => config_path('laravel_dev.php'),
        ], 'config');
    }
}
