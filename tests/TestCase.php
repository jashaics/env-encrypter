<?php

namespace Jashaics\EnvEncrypter\Tests;

use Jashaics\EnvEncrypter\EnvEncrypterServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            EnvEncrypterServiceProvider::class,
        ];
    }
}
