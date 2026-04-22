<?php

namespace Jashaics\EnvEncrypter\Tests\Feature;

use Jashaics\EnvEncrypter\Http\Controllers\API\EnvEncrypterController;
use Jashaics\EnvEncrypter\Tests\Helpers\FunctionMock;
use Jashaics\EnvEncrypter\Tests\TestCase;

class TestableEnvEncrypterController extends EnvEncrypterController
{
    public bool $terminateCalled = false;
    public int $terminateCode = 0;

    protected function terminate(int $code): void
    {
        $this->terminateCalled = true;
        $this->terminateCode = $code;
    }
}

class EnvEncrypterControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $_POST = [];
    }

    protected function tearDown(): void
    {
        FunctionMock::reset();
        $_POST = [];

        parent::tearDown();
    }

    private TestableEnvEncrypterController $controller;

    private function callController(): array
    {
        $this->controller = new TestableEnvEncrypterController();
        ob_start();
        $this->controller->setupEnv();
        return json_decode(ob_get_clean(), true);
    }

    private function encryptedPayload(string $data, string $key): string
    {
        $cipher = 'aes-256-cbc';
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $encrypted = openssl_encrypt($data, $cipher, base64_encode($key), 0, $iv);

        return base64_encode($iv . $encrypted);
    }

    private function assertErrorResponse(array $response, string $message): void
    {
        $this->assertTrue($response['error']);
        $this->assertEquals($message, $response['message']);
        $this->assertTrue($this->controller->terminateCalled);
        $this->assertEquals(1, $this->controller->terminateCode);
    }

    public function test_returns_error_when_source_param_is_missing(): void
    {
        $_POST = ['destination' => '.env', 'key' => 'secret'];

        $this->assertErrorResponse($this->callController(), 'Errore nei parametri');
    }

    public function test_returns_error_when_destination_param_is_missing(): void
    {
        $_POST = ['source' => '.env.encrypted', 'key' => 'secret'];

        $this->assertErrorResponse($this->callController(), 'Errore nei parametri');
    }

    public function test_returns_error_when_key_param_is_missing(): void
    {
        $_POST = ['source' => '.env.encrypted', 'destination' => '.env'];

        $this->assertErrorResponse($this->callController(), 'Errore nei parametri');
    }

    public function test_returns_error_when_source_param_is_not_a_string(): void
    {
        $_POST = ['source' => 123, 'destination' => '.env', 'key' => 'secret'];

        $this->assertErrorResponse($this->callController(), 'I parametri devono essere stringhe');
    }

    public function test_returns_error_when_destination_param_is_not_a_string(): void
    {
        $_POST = ['source' => '.env.encrypted', 'destination' => 123, 'key' => 'secret'];

        $this->assertErrorResponse($this->callController(), 'I parametri devono essere stringhe');
    }

    public function test_returns_error_when_key_param_is_not_a_string(): void
    {
        $_POST = ['source' => '.env.encrypted', 'destination' => '.env', 'key' => 123];

        $this->assertErrorResponse($this->callController(), 'I parametri devono essere stringhe');
    }

    public function test_returns_error_when_source_contains_path_traversal(): void
    {
        $_POST = ['source' => '../.env.encrypted', 'destination' => '.env', 'key' => 'secret'];

        $this->assertErrorResponse($this->callController(), 'Sorgente non valida');
    }

    public function test_returns_error_when_destination_contains_path_traversal(): void
    {
        $_POST = ['source' => '.env.encrypted', 'destination' => '../.env', 'key' => 'secret'];

        $this->assertErrorResponse($this->callController(), 'Destinazione non valida');
    }

    public function test_returns_error_when_source_file_cannot_be_read(): void
    {
        FunctionMock::$fileGetContentsReturnsFalse = true;
        $_POST = ['source' => '.env.encrypted', 'destination' => '.env', 'key' => 'secret'];

        $this->assertErrorResponse($this->callController(), 'Errore nella lettura del file sorgente');
    }

    public function test_returns_error_when_source_file_is_empty(): void
    {
        FunctionMock::$fileGetContentsReturns = '';
        $_POST = ['source' => '.env.encrypted', 'destination' => '.env', 'key' => 'secret'];

        $this->assertErrorResponse($this->callController(), 'File sorgente vuoto');
    }

    public function test_returns_error_when_destination_cannot_be_written(): void
    {
        $key = str_repeat('a', 32);
        FunctionMock::$fileGetContentsReturns = $this->encryptedPayload('APP_NAME=Test', $key);
        FunctionMock::$filePutContentsReturnsFalse = true;
        $_POST = ['source' => '.env.encrypted', 'destination' => '.env', 'key' => $key];

        $this->assertErrorResponse($this->callController(), 'Errore nella scrittura del file di destinazione');
    }

    public function test_returns_success_when_env_file_is_decrypted(): void
    {
        $key = str_repeat('a', 32);
        $originalContent = "APP_NAME=TestApp\nAPP_ENV=production";
        FunctionMock::$fileGetContentsReturns = $this->encryptedPayload($originalContent, $key);
        $_POST = ['source' => '.env.encrypted', 'destination' => '.env', 'key' => $key];

        $response = $this->callController();

        $this->assertFalse($response['error']);
        $this->assertEquals('Environment file decrypted successfully', $response['message']);
        $this->assertFalse($this->controller->terminateCalled);
    }

    public function test_http_code_falls_back_to_500_when_response_code_is_unavailable(): void
    {
        FunctionMock::$httpResponseCodeGetReturnsFalse = true;
        FunctionMock::$fileGetContentsReturnsFalse = true;
        $_POST = ['source' => '.env.encrypted', 'destination' => '.env', 'key' => 'secret'];

        $response = $this->callController();

        $this->assertTrue($response['error']);
        $this->assertEquals(500, $response['http_code']);
        $this->assertTrue($this->controller->terminateCalled);
    }

    public function test_terminate_exits_with_provided_code(): void
    {
        $autoload = realpath(__DIR__ . '/../../vendor/autoload.php');
        $script = sprintf(
            'require %s; class TC extends \Jashaics\EnvEncrypter\Http\Controllers\API\EnvEncrypterController { public function run(int $code): void { $this->terminate($code); } } (new TC())->run(1);',
            var_export($autoload, true)
        );

        exec(PHP_BINARY . ' -r ' . escapeshellarg($script), $output, $exitCode);

        $this->assertEquals(1, $exitCode);
    }
}
