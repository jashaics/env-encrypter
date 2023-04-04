<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Jashaics\EnvEncrypter\Tests\TestCase;

class CommandTest extends TestCase
{
    public function testEncryption()
    {
        $this->assertTrue(File::exists($this->getFilePathInPackage($this->localEnvFile)));

        Artisan::call('env-encrypter:encrypt');

        $this->assertTrue(File::exists($this->getFilePathInPackage($this->localEnvFile.'.encrypted')));

        // $this->assertTrue(File::exists(config_path('blogpackage.php')));
    }
}
