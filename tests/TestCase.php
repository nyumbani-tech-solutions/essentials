<?php

namespace Nyumbani\Essentials\Tests;

use Nyumbani\Essentials\EssentialsServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Boot the package so its helpers, directives and config load in every test
     *
     * @author Mark Kilonzi <markkilonzi24@gmail.com>
     */
    protected function getPackageProviders($app): array
    {
        return [EssentialsServiceProvider::class];
    }
}
