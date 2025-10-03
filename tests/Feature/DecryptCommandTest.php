<?php

namespace Jashaics\EnvEncrypter\Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DecryptCommandTest extends TestCase
{
    protected string $key;

    protected const NAME = '.env.test';

    protected const ENCRYPTED_NAME = '.env.test.encrypted';

    protected const DECRYPTED_NAME = '.env.test.decrypted';

    protected function setUp(): void
    {
        parent::setUp();

        // Create and encrypt test file
        File::put(self::NAME, "APP_NAME=TestApp\nAPP_ENV=testing\nAPP_KEY=base64:test123");

        $this->key = str_repeat('a', 20);

        // Encrypt the file
        /** @var \Illuminate\Testing\PendingCommand $result */
        $result = $this->artisan('env-encrypter:encrypt', [
            '--source' => self::NAME,
            '--destination' => self::ENCRYPTED_NAME,
            '--key' => $this->key,
            '--force' => true,
            '--quiet' => true,
        ]);

        if ($result->run() !== 0) {
            throw new \RuntimeException('Encrypt command failed in setUp');
        }
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if (File::exists(self::NAME)) {
            File::delete(self::NAME);
        }
        if (File::exists(self::ENCRYPTED_NAME)) {
            File::delete(self::ENCRYPTED_NAME);
        }
        if (File::exists(self::DECRYPTED_NAME)) {
            File::delete(self::DECRYPTED_NAME);
        }
        File::delete(File::glob(self::NAME.'*.backup'));

        parent::tearDown();
    }

    public function test_decrypt_command_decrypts_file_successfully(): void
    {
        // deleting file to test that is created
        File::delete(self::NAME);
        $this->assertFalse(File::exists(self::NAME));

        /** @var \Illuminate\Testing\PendingCommand $command */
        $command = $this->artisan('env-encrypter:decrypt', [
            '--source' => self::ENCRYPTED_NAME,
            '--destination' => self::NAME,
            '--key' => $this->key,
            '--force' => true,
            '--quiet' => true,
        ]);

        $exitCode = $command->run();

        if ($exitCode !== 0) {
            $this->fail('Command failed with exit code: ' . $exitCode);
        }

        // Give the filesystem time to sync
        clearstatcache();

        $this->assertTrue(file_exists(self::NAME), 'Decrypted file does not exist');

        $content = file_get_contents(self::NAME);
        $this->assertNotFalse($content, 'Failed to read file content');
        $this->assertStringContainsString('APP_NAME=TestApp', $content);
    }

    public function test_decrypt_command_creates_decrypted_file(): void
    {
        $this->assertTrue(File::exists(self::ENCRYPTED_NAME));

        /** @var \Illuminate\Testing\PendingCommand $command */
        $command = $this->artisan('env-encrypter:decrypt', [
            '--source' => self::ENCRYPTED_NAME,
            '--destination' => self::DECRYPTED_NAME,
            '--key' => $this->key,
            '--force' => true,
            '--quiet' => true,
        ]);
        $exitCode = $command->run();

        if ($exitCode !== 0) {
            $this->fail('Command failed with exit code: ' . $exitCode);
        }

        // Give the filesystem time to sync
        clearstatcache();

        $this->assertTrue(File::exists(self::DECRYPTED_NAME));
        $this->assertStringContainsString('APP_NAME=TestApp', File::get(self::DECRYPTED_NAME));
    }

    public function test_decrypt_with_wrong_key_fails(): void
    {
        $this->assertTrue(File::exists(self::ENCRYPTED_NAME));

        $wrongKey = str_repeat('b', 20);

        /** @var \Illuminate\Testing\PendingCommand $command */
        $command = $this->artisan('env-encrypter:decrypt', [
            '--source' => self::ENCRYPTED_NAME,
            '--destination' => self::NAME,
            '--key' => $wrongKey,
            '--force' => true,
            '--quiet' => true,
        ]);
        $command->assertExitCode(1);
    }
}
