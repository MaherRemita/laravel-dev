<?php

namespace maherremita\LaravelDev\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use maherremita\LaravelDev\LaravelDevServiceProvider;

abstract class TestCase extends Orchestra
{

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelDevServiceProvider::class,
        ];
    }
}
