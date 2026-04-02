<?php

namespace Guava\FilamentDrafts\Tests;

use Guava\FilamentDrafts\FilamentDraftsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            FilamentDraftsServiceProvider::class,
        ];
    }
}
