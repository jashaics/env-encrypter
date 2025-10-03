<?php

namespace Jashaics\EnvEncrypter\Tests\Unit;

use Illuminate\Console\Command;
use Jashaics\EnvEncrypter\Console\Commands\EncryptionTrait;
use Tests\TestCase;

class TestCommand extends Command
{
    use EncryptionTrait;

    protected $signature = 'test:command';
    protected string $action = 'encrypt';

    public function testEncryptData(string $data, string $key): string
    {
        return $this->encryptData($data, $key);
    }

    public function testDecryptData(string $encryptedData, string $key): string
    {
        return $this->decryptData($encryptedData, $key);
    }

    public function testDefineKey(?string $key): string
    {
        return $this->defineKey($key);
    }
}


class EncryptionTraitTest extends TestCase
{
    protected TestCommand $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->command = new TestCommand();
    }

    public function test_encrypt_data_encrypts_successfully(): void
    {
        $data = 'test data';
        $key = str_repeat('a', 20);

        $encrypted = $this->command->testEncryptData($data, $key);

        $this->assertNotEquals($data, $encrypted);
        $this->assertNotEmpty($encrypted);
        $this->assertGreaterThan(0, strlen($encrypted));
    }

    public function test_decrypt_data_decrypts_successfully(): void
    {
        $data = 'test data';
        $key = str_repeat('a', 20);

        $encrypted = $this->command->testEncryptData($data, $key);
        $decrypted = $this->command->testDecryptData($encrypted, $key);

        $this->assertEquals($data, $decrypted);
    }

    public function test_encrypt_and_decrypt_are_reversible(): void
    {
        $data = "APP_NAME=TestApp\nAPP_ENV=testing\nAPP_KEY=base64:test123\nDB_CONNECTION=mysql";
        $key = str_repeat('a', 32);

        $encrypted = $this->command->testEncryptData($data, $key);
        $decrypted = $this->command->testDecryptData($encrypted, $key);

        $this->assertEquals($data, $decrypted);
    }

    public function test_decrypt_data_throws_exception_with_invalid_key(): void
    {
        $this->expectException(\Exception::class);

        $data = 'test data';
        $key = str_repeat('a', 20);
        $wrongKey = str_repeat('b', 20);

        $encrypted = $this->command->testEncryptData($data, $key);
        $this->command->testDecryptData($encrypted, $wrongKey);
    }

    public function test_decrypt_data_throws_exception_with_invalid_base64(): void
    {
        $this->expectException(\Exception::class);

        $key = str_repeat('a', 20);
        $this->command->testDecryptData('invalid_base64', $key);
    }

    public function test_encrypt_data_works_with_valid_cipher(): void
    {
        $data = 'test data';
        $key = str_repeat('a', 20);

        $encrypted = $this->command->testEncryptData($data, $key);
        $this->assertNotEmpty($encrypted);
    }

    public function test_define_key_returns_valid_key_when_provided(): void
    {
        $key = str_repeat('a', 32);
        $result = $this->command->testDefineKey($key);

        $this->assertEquals($key, $result);
    }

    public function test_encryption_produces_different_output_for_same_input(): void
    {
        // Due to random IV, same input should produce different encrypted output
        $data = 'test data';
        $key = str_repeat('a', 20);

        $encrypted1 = $this->command->testEncryptData($data, $key);
        $encrypted2 = $this->command->testEncryptData($data, $key);

        $this->assertNotEquals($encrypted1, $encrypted2);

        // But both should decrypt to the same value
        $decrypted1 = $this->command->testDecryptData($encrypted1, $key);
        $decrypted2 = $this->command->testDecryptData($encrypted2, $key);

        $this->assertEquals($data, $decrypted1);
        $this->assertEquals($data, $decrypted2);
    }

    public function test_encryption_handles_empty_strings(): void
    {
        $data = '';
        $key = str_repeat('a', 20);

        $encrypted = $this->command->testEncryptData($data, $key);
        $decrypted = $this->command->testDecryptData($encrypted, $key);

        $this->assertEquals($data, $decrypted);
    }

    public function test_encryption_handles_special_characters(): void
    {
        $data = "Special chars: !@#$%^&*()_+{}[]|\\:\";<>?,./\n\t\r";
        $key = str_repeat('a', 20);

        $encrypted = $this->command->testEncryptData($data, $key);
        $decrypted = $this->command->testDecryptData($encrypted, $key);

        $this->assertEquals($data, $decrypted);
    }

    public function test_encryption_handles_unicode_characters(): void
    {
        $data = "Unicode: 你好世界 🔒 مرحبا العالم";
        $key = str_repeat('a', 20);

        $encrypted = $this->command->testEncryptData($data, $key);
        $decrypted = $this->command->testDecryptData($encrypted, $key);

        $this->assertEquals($data, $decrypted);
    }

    public function test_encryption_handles_large_data(): void
    {
        $data = str_repeat('Large data block ', 1000);
        $key = str_repeat('a', 20);

        $encrypted = $this->command->testEncryptData($data, $key);
        $decrypted = $this->command->testDecryptData($encrypted, $key);

        $this->assertEquals($data, $decrypted);
    }
}
