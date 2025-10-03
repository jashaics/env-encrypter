<?php

namespace Jashaics\EnvEncrypter\Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class EncryptCommandTest extends TestCase
{
    protected string $key;

    protected const NAME = '.env.test';

    protected const ENCRYPTED_NAME = '.env.test.encrypted';

    protected function setUp(): void
    {
        parent::setUp();

        // Create test files
        File::put(self::NAME, "APP_NAME=TestApp\nAPP_ENV=testing\nAPP_KEY=base64:test123");

        $this->key = str_repeat('a', 20);
    }

    protected function tearDown(): void
    {
        // Clean up test files
        File::delete(self::NAME);
        if (File::exists(self::ENCRYPTED_NAME)) {
            File::delete(self::ENCRYPTED_NAME);
        }
        File::delete(File::glob(self::NAME.'*.backup'));

        parent::tearDown();
    }

    public function test_encrypt_command_encrypts_file_successfully(): void
    {
        $this->assertTrue(File::exists(self::NAME));

        /** @var \Illuminate\Testing\PendingCommand $command */
        $command = $this->artisan('env-encrypter:encrypt', [
            '--source' => self::NAME,
            '--destination' => self::ENCRYPTED_NAME,
            '--key' => $this->key,
            '--quiet' => true,
        ]);

        $exitCode = $command->run();

        if ($exitCode !== 0) {
            $this->fail('Command failed with exit code: ' . $exitCode);
        }

        // Give the filesystem time to sync
        clearstatcache();

        $this->assertTrue(File::exists(self::ENCRYPTED_NAME));
        $this->assertNotEquals(File::get(self::NAME), File::get(self::ENCRYPTED_NAME));
    }

    public function test_encrypted_file_can_be_decrypted(): void
    {
        $originalContent = File::get(self::NAME);

        // Encrypt
        /** @var \Illuminate\Testing\PendingCommand $command */
        $command = $this->artisan('env-encrypter:encrypt', [
            '--source' => self::NAME,
            '--destination' => self::ENCRYPTED_NAME,
            '--key' => $this->key,
            '--quiet' => true,
        ]);
        $exitCode = $command->run();

        if ($exitCode !== 0) {
            $this->fail('Command failed with exit code: ' . $exitCode);
        }

        // Give the filesystem time to sync
        clearstatcache();
        $this->assertTrue(File::exists(self::ENCRYPTED_NAME));

        // Delete original
        File::delete(self::NAME);

        // Decrypt
        /** @var \Illuminate\Testing\PendingCommand $command */
        $command = $this->artisan('env-encrypter:decrypt', [
            '--source' => self::ENCRYPTED_NAME,
            '--destination' => self::NAME,
            '--key' => $this->key,
            '--quiet' => true,
        ]);

        $exitCode = $command->run();

        if ($exitCode !== 0) {
            $this->fail('Command failed with exit code: ' . $exitCode);
        }

        // Give the filesystem time to sync
        clearstatcache();

        // Verify content matches
        $this->assertEquals($originalContent, File::get(self::NAME));
    }
}
