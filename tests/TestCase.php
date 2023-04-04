<?php

namespace Jashaics\EnvEncrypter\Tests;

use Jashaics\EnvEncrypter\EnvEncrypterServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected string $localEnvFile = '.env';

    protected string $previewEnvFile = '.env.preview';

    protected string $productionEnvFile = '.env.production';

    public function setUp(): void
    {
        parent::setUp();

        // creating .env files
        file_put_contents($this->localEnvFile, '# this is local env file');
        file_put_contents($this->previewEnvFile, '# this is preview env file');
        file_put_contents($this->productionEnvFile, '# this is prodduction env file');
    }

    public function tearDown(): void
    {
        parent::tearDown();

        // cleaning up .env files
        unset($this->localEnvFile);
        unset($this->previewEnvFile);
        unset($this->productionEnvFile);
    }

    protected function getPackageProviders($app)
    {
        return [
            EnvEncrypterServiceProvider::class,
        ];
    }

    /**
     * Given a file path, return the path to the file in the package
     */
    protected function getFilePathInPackage(string $filePath): string
    {
        return realpath(__DIR__.'/../').'/'.trim($filePath, '/');
    }

    // protected function getEnvironmentSetUp($app)
    // {
    //   // perform environment setup
    // }
}
