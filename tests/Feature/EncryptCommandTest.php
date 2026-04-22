<?php

namespace Jashaics\EnvEncrypter\Tests\Feature;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Jashaics\EnvEncrypter\Tests\Helpers\FunctionMock;
use Jashaics\EnvEncrypter\Tests\TestCase;

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
        FunctionMock::reset();

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

    public function test_encrypt_command_handles_existing_backup_file(): void
    {
        // Create an existing backup file to force the while loop to iterate (covers line 89)
        File::put(self::NAME.'.backup', 'existing backup placeholder');

        $command = $this->artisan('env-encrypter:encrypt', [
            '--source' => self::NAME,
            '--destination' => self::ENCRYPTED_NAME,
            '--key' => $this->key,
            '--quiet' => true,
        ]);
        $command->assertExitCode(Command::SUCCESS);
    }

    public function test_encrypt_command_fails_when_writes_fail(): void
    {
        // Mock all File::put calls to fail, covering the backup-write-fail branch (lines 94-95)
        // and the destination-write-fail branch (lines 110-112) in a single test.
        $this->app->instance('files', new class extends \Illuminate\Filesystem\Filesystem {
            public function put($path, $contents, $lock = false): int|false
            {
                return false;
            }
        });
        \Illuminate\Support\Facades\File::clearResolvedInstance('files');

        $command = $this->artisan('env-encrypter:encrypt', [
            '--source' => self::NAME,
            '--destination' => self::ENCRYPTED_NAME,
            '--key' => $this->key,
            '--force' => true,
            '--quiet' => true,
        ]);
        $command->assertExitCode(Command::FAILURE);
    }

    public function test_encrypt_command_fails_when_encryption_throws(): void
    {
        // Force openssl_cipher_iv_length to return false so encryptData throws,
        // covering the catch block (lines 102-104) in Encrypt::handle.
        FunctionMock::$opensslCipherIvLengthCommandReturnsFalse = true;

        $command = $this->artisan('env-encrypter:encrypt', [
            '--source' => self::NAME,
            '--destination' => self::ENCRYPTED_NAME,
            '--key' => $this->key,
            '--force' => true,
            '--quiet' => true,
        ]);
        $command->assertExitCode(Command::FAILURE);
    }
}
