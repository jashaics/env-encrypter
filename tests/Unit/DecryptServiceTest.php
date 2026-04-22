<?php

namespace Jashaics\EnvEncrypter\Tests\Unit;

use Exception;
use Jashaics\EnvEncrypter\Services\DecryptService;
use Jashaics\EnvEncrypter\Tests\Helpers\FunctionMock;
use Jashaics\EnvEncrypter\Tests\TestCase;

class DecryptServiceTest extends TestCase
{
    private string $key;

    protected function setUp(): void
    {
        parent::setUp();

        $this->key = str_repeat('a', 32);
    }

    protected function tearDown(): void
    {
        FunctionMock::reset();

        parent::tearDown();
    }

    private function encrypt(string $data, string $key): string
    {
        $cipher = 'aes-256-cbc';
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $encrypted = openssl_encrypt($data, $cipher, base64_encode($key), 0, $iv);

        return base64_encode($iv . $encrypted);
    }

    public function test_decrypts_valid_data_successfully(): void
    {
        $data = 'APP_NAME=TestApp';

        $result = DecryptService::decryptEnv($this->encrypt($data, $this->key), $this->key);

        $this->assertEquals($data, $result);
    }

    public function test_decrypts_multiline_env_content(): void
    {
        $data = "APP_NAME=TestApp\nAPP_ENV=testing\nAPP_KEY=base64:test123\nDB_CONNECTION=mysql";

        $result = DecryptService::decryptEnv($this->encrypt($data, $this->key), $this->key);

        $this->assertEquals($data, $result);
    }

    public function test_decrypts_data_with_special_characters(): void
    {
        $data = "SECRET=p@ssw0rd!#$%^&*()_+{}[]|\\:\";<>?,./";

        $result = DecryptService::decryptEnv($this->encrypt($data, $this->key), $this->key);

        $this->assertEquals($data, $result);
    }

    public function test_decrypts_data_with_unicode_characters(): void
    {
        $data = "APP_NAME=Applicazione\nDESCRIPTION=你好世界 🔒 مرحبا";

        $result = DecryptService::decryptEnv($this->encrypt($data, $this->key), $this->key);

        $this->assertEquals($data, $result);
    }

    public function test_decrypts_empty_string(): void
    {
        $data = '';

        $result = DecryptService::decryptEnv($this->encrypt($data, $this->key), $this->key);

        $this->assertEquals($data, $result);
    }

    public function test_throws_exception_when_data_is_not_valid_encrypted_content(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Errore durante la decodifica');

        // valid base64 but not a real encrypted payload (too short for a full AES block)
        DecryptService::decryptEnv('123456123456123456123456', $this->key);
    }

    public function test_throws_exception_when_key_is_wrong(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Errore durante la decodifica');

        $encrypted = $this->encrypt('APP_NAME=TestApp', $this->key);

        DecryptService::decryptEnv($encrypted, str_repeat('b', 32));
    }

    public function test_throws_exception_when_key_has_different_length(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Errore durante la decodifica');

        $encrypted = $this->encrypt('APP_NAME=TestApp', $this->key);

        DecryptService::decryptEnv($encrypted, str_repeat('a', 20));
    }

    public function test_throws_exception_when_openssl_iv_length_fails(): void
    {
        FunctionMock::$opensslCipherIvLengthReturnsFalse = true;

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Errore nella lettura del vettore di inizializzazione');

        DecryptService::decryptEnv('anydata', $this->key);
    }

    public function test_throws_exception_when_base64_decode_fails(): void
    {
        FunctionMock::$base64DecodeReturnsFalse = true;

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Errore nella decodifica base64 dei dati');

        DecryptService::decryptEnv('anydata', $this->key);
    }

    public function test_decryption_is_consistent_across_multiple_calls(): void
    {
        $data = 'APP_NAME=TestApp';
        $encrypted = $this->encrypt($data, $this->key);

        $result1 = DecryptService::decryptEnv($encrypted, $this->key);
        $result2 = DecryptService::decryptEnv($encrypted, $this->key);

        $this->assertEquals($result1, $result2);
    }
}
