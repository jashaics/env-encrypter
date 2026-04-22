<?php

namespace Jashaics\EnvEncrypter\Tests\Unit;

use Exception;
use Illuminate\Console\Command;
use Jashaics\EnvEncrypter\Console\Commands\EncryptionTrait;
use Jashaics\EnvEncrypter\Tests\Helpers\FunctionMock;
use Jashaics\EnvEncrypter\Tests\TestCase;
use Laravel\Prompts\Prompt;

class TestCommand extends Command
{
    use EncryptionTrait;

    protected $signature = 'test:command';
    protected string $action = 'encrypt';
    protected bool $forceOption = false;

    public function setAction(string $action): void
    {
        $this->action = $action;
    }

    public function setForce(bool $force): void
    {
        $this->forceOption = $force;
    }

    public function option($key = null): ?bool
    {
        if ($key === 'force') {
            return $this->forceOption;
        }

        return null;
    }

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

    public function testDefineClearFilename(?string $filename, ?string $encryptedFileName = null): string
    {
        return $this->defineClearFilename($filename, $encryptedFileName);
    }

    public function testDefineEncryptedFilename(?string $filename, ?string $clearFileName = null): string
    {
        return $this->defineEncryptedFilename($filename, $clearFileName);
    }

    public function testHasName(?string $filename): bool
    {
        return $this->hasName($filename);
    }

    public function testHasForbiddenCharacters(string $filename): bool
    {
        return $this->hasforbiddenCharacters($filename);
    }

    public function testHasEnvInName(string $filename): bool
    {
        return $this->hasEnvInName($filename);
    }

    public function testHasFile(string $filename, bool $dontShowError = false): bool
    {
        return $this->hasFile($filename, $dontShowError);
    }

    public function testStartsWithDot(string $filename): bool
    {
        return $this->startsWithDot($filename);
    }
}


class EncryptionTraitTest extends TestCase
{
    protected TestCommand $command;

    protected function setUp(): void
    {
        parent::setUp();

        Prompt::fake();
        $this->command = new TestCommand();
    }

    protected function tearDown(): void
    {
        FunctionMock::reset();

        parent::tearDown();
    }

    public function test_has_name_returns_false_for_null(): void
    {
        $this->assertFalse($this->command->testHasName(null));
    }

    public function test_has_name_returns_false_for_empty_string(): void
    {
        $this->assertFalse($this->command->testHasName(''));
    }

    public function test_has_name_returns_true_for_non_empty_string(): void
    {
        $this->assertTrue($this->command->testHasName('.env'));
    }

    public function test_has_forbidden_characters_returns_true_for_valid_filename(): void
    {
        $this->assertTrue($this->command->testHasForbiddenCharacters('.env'));
        $this->assertTrue($this->command->testHasForbiddenCharacters('.env.encrypted'));
        $this->assertTrue($this->command->testHasForbiddenCharacters('env_backup-1'));
    }

    public function test_has_forbidden_characters_returns_false_for_spaces(): void
    {
        $this->assertFalse($this->command->testHasForbiddenCharacters('.env file'));
    }

    public function test_has_forbidden_characters_returns_false_for_double_dot(): void
    {
        $this->assertFalse($this->command->testHasForbiddenCharacters('..env'));
    }

    public function test_has_env_in_name_returns_true_when_env_present(): void
    {
        $this->assertTrue($this->command->testHasEnvInName('.env'));
        $this->assertTrue($this->command->testHasEnvInName('.env.encrypted'));
    }

    public function test_has_env_in_name_returns_false_when_env_absent(): void
    {
        $this->assertFalse($this->command->testHasEnvInName('config.txt'));
    }

    public function test_has_file_returns_true_when_file_exists(): void
    {
        FunctionMock::$fileExistsReturns = true;

        $this->assertTrue($this->command->testHasFile('.env'));
    }

    public function test_has_file_returns_false_with_error_when_file_missing(): void
    {
        FunctionMock::$fileExistsReturns = false;

        $this->assertFalse($this->command->testHasFile('.env', false));
    }

    public function test_has_file_returns_false_without_error_when_dont_show_error_is_true(): void
    {
        FunctionMock::$fileExistsReturns = false;

        $this->assertFalse($this->command->testHasFile('.env', true));
    }

    public function test_starts_with_dot_returns_true_for_dotfile(): void
    {
        $this->assertTrue($this->command->testStartsWithDot('.env'));
    }

    public function test_starts_with_dot_returns_false_for_no_dot(): void
    {
        $this->assertFalse($this->command->testStartsWithDot('env'));
    }

    public function test_encrypt_data_encrypts_successfully(): void
    {
        $data = 'APP_NAME=TestApp';
        $key = str_repeat('a', 32);

        $encrypted = $this->command->testEncryptData($data, $key);

        $this->assertNotEquals($data, $encrypted);
        $this->assertNotEmpty($encrypted);
    }

    public function test_encrypt_data_throws_when_iv_length_fails(): void
    {
        FunctionMock::$opensslCipherIvLengthCommandReturnsFalse = true;

        $this->expectException(Exception::class);

        $this->command->testEncryptData('data', str_repeat('a', 32));
    }

    public function test_decrypt_data_decrypts_successfully(): void
    {
        $key = str_repeat('a', 32);
        $encrypted = $this->command->testEncryptData('APP_NAME=TestApp', $key);

        $this->assertEquals('APP_NAME=TestApp', $this->command->testDecryptData($encrypted, $key));
    }

    public function test_decrypt_data_throws_when_iv_length_fails(): void
    {
        FunctionMock::$opensslCipherIvLengthCommandReturnsFalse = true;

        $this->expectException(Exception::class);

        $this->command->testDecryptData('somedata', str_repeat('a', 32));
    }

    public function test_decrypt_data_throws_with_invalid_base64(): void
    {
        FunctionMock::$base64DecodeCommandReturnsFalse = true;

        $this->expectException(Exception::class);

        $this->command->testDecryptData('anydata', str_repeat('a', 32));
    }

    public function test_decrypt_data_throws_with_wrong_key(): void
    {
        $key = str_repeat('a', 32);
        $encrypted = $this->command->testEncryptData('APP_NAME=TestApp', $key);

        $this->expectException(Exception::class);

        $this->command->testDecryptData($encrypted, str_repeat('b', 32));
    }

    public function test_encrypt_and_decrypt_are_reversible(): void
    {
        $data = "APP_NAME=TestApp\nAPP_ENV=testing";
        $key = str_repeat('a', 32);

        $encrypted = $this->command->testEncryptData($data, $key);

        $this->assertEquals($data, $this->command->testDecryptData($encrypted, $key));
    }

    public function test_define_key_returns_valid_key_when_long_enough(): void
    {
        $key = str_repeat('a', 32);

        $this->assertEquals($key, $this->command->testDefineKey($key));
    }

    public function test_define_key_returns_key_on_decrypt_even_if_short(): void
    {
        $this->command->setAction('decrypt');
        $shortKey = 'short';

        $this->assertEquals($shortKey, $this->command->testDefineKey($shortKey));
    }

    public function test_define_key_prompts_for_key_when_too_short_on_encrypt(): void
    {
        $validKey = str_repeat('a', 20);
        Prompt::fake([...str_split($validKey), "\n"]);

        $result = $this->command->testDefineKey('tooshort');

        $this->assertEquals($validKey, $result);
    }

    public function test_define_key_prompts_for_key_when_empty(): void
    {
        $validKey = str_repeat('a', 20);
        Prompt::fake([...str_split($validKey), "\n"]);

        $result = $this->command->testDefineKey('');

        $this->assertEquals($validKey, $result);
    }

    public function test_define_clear_filename_returns_valid_filename_for_encrypt(): void
    {
        FunctionMock::$fileExistsReturns = true;
        $this->command->setAction('encrypt');

        $this->assertEquals('.env', $this->command->testDefineClearFilename('.env'));
    }

    public function test_define_clear_filename_adds_dot_prefix_for_encrypt(): void
    {
        FunctionMock::$fileExistsReturns = true;
        $this->command->setAction('encrypt');

        $this->assertEquals('.env', $this->command->testDefineClearFilename('env'));
    }

    public function test_define_clear_filename_returns_valid_filename_for_decrypt_when_no_existing_file(): void
    {
        FunctionMock::$fileExistsReturns = false;
        $this->command->setAction('decrypt');

        $this->assertEquals('.env', $this->command->testDefineClearFilename('.env'));
    }

    public function test_define_clear_filename_returns_filename_for_decrypt_when_user_confirms_overwrite(): void
    {
        FunctionMock::$fileExistsReturns = true;
        $this->command->setAction('decrypt');
        Prompt::fake(["\n"]); // Enter accepts the default (true)

        $this->assertEquals('.env', $this->command->testDefineClearFilename('.env'));
    }

    public function test_define_clear_filename_returns_filename_for_decrypt_with_force(): void
    {
        FunctionMock::$fileExistsReturns = true;
        $this->command->setAction('decrypt');
        $this->command->setForce(true);

        $this->assertEquals('.env', $this->command->testDefineClearFilename('.env'));
    }

    public function test_define_encrypted_filename_returns_valid_filename_for_decrypt(): void
    {
        FunctionMock::$fileExistsReturns = true;
        $this->command->setAction('decrypt');

        $this->assertEquals('.env.encrypted', $this->command->testDefineEncryptedFilename('.env.encrypted'));
    }

    public function test_define_encrypted_filename_adds_dot_prefix_for_decrypt(): void
    {
        FunctionMock::$fileExistsReturns = true;
        $this->command->setAction('decrypt');

        $this->assertEquals('.env.encrypted', $this->command->testDefineEncryptedFilename('env.encrypted'));
    }

    public function test_define_encrypted_filename_returns_valid_filename_for_encrypt_when_no_existing_file(): void
    {
        FunctionMock::$fileExistsReturns = false;
        $this->command->setAction('encrypt');

        $this->assertEquals('.env.encrypted', $this->command->testDefineEncryptedFilename('.env'));
    }

    public function test_define_encrypted_filename_appends_encrypted_suffix(): void
    {
        FunctionMock::$fileExistsReturns = false;
        $this->command->setAction('encrypt');

        $result = $this->command->testDefineEncryptedFilename('.env');

        $this->assertStringEndsWith('.encrypted', $result);
    }

    public function test_define_encrypted_filename_returns_filename_for_encrypt_when_user_confirms_overwrite(): void
    {
        FunctionMock::$fileExistsReturns = true;
        $this->command->setAction('encrypt');
        Prompt::fake(["\n"]); // Enter accepts the default (true)

        $this->assertEquals('.env.encrypted', $this->command->testDefineEncryptedFilename('.env.encrypted'));
    }

    public function test_define_encrypted_filename_returns_filename_for_encrypt_with_force(): void
    {
        FunctionMock::$fileExistsReturns = true;
        $this->command->setAction('encrypt');
        $this->command->setForce(true);

        $this->assertEquals('.env.encrypted', $this->command->testDefineEncryptedFilename('.env.encrypted'));
    }
}
