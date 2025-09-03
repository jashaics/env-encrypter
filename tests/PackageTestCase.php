<?php

declare(strict_types=1);

namespace Jashaics\EnvEncrypter\Tests;

use Jashaics\EnvEncrypter\EnvEncrypterServiceProvider;
use Orchestra\Testbench\TestCase;

class PackageTestCase extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            EnvEncrypterServiceProvider::class,
        ];
    }
}
