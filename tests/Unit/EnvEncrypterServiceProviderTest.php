<?php

namespace Jashaics\EnvEncrypter\Tests\Unit;

use Illuminate\Support\Facades\Artisan;
use Jashaics\EnvEncrypter\EnvEncrypterServiceProvider;
use Tests\TestCase;

class EnvEncrypterServiceProviderTest extends TestCase
{
    public function test_service_provider_registers_commands(): void
    {
        $provider = new EnvEncrypterServiceProvider($this->app);
        $provider->boot();

        $this->assertTrue($this->app->runningInConsole());
    }

    public function test_service_provider_loads_translations(): void
    {
        $provider = new EnvEncrypterServiceProvider($this->app);
        $provider->boot();

        $this->assertNotEmpty(trans('env-encrypter::errors.characters_not_allowed'));
    }

    public function test_encrypt_command_is_registered(): void
    {
        $this->assertArrayHasKey('env-encrypter:encrypt', Artisan::all());
    }

    public function test_decrypt_command_is_registered(): void
    {
        $this->assertArrayHasKey('env-encrypter:decrypt', Artisan::all());
    }
}
