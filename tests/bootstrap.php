<?php

namespace {
    require_once __DIR__ . '/../vendor/autoload.php';
}

namespace Jashaics\EnvEncrypter\Http\Controllers\API {
    function file_get_contents(string $filename, mixed ...$args): string|false
    {
        if (\Jashaics\EnvEncrypter\Tests\Helpers\FunctionMock::$fileGetContentsReturnsFalse) {
            return false;
        }

        if (\Jashaics\EnvEncrypter\Tests\Helpers\FunctionMock::$fileGetContentsReturns !== null) {
            return \Jashaics\EnvEncrypter\Tests\Helpers\FunctionMock::$fileGetContentsReturns;
        }

        return \file_get_contents($filename, ...$args);
    }

    function file_put_contents(string $filename, mixed $data, int $flags = 0, mixed ...$args): int|false
    {
        if (\Jashaics\EnvEncrypter\Tests\Helpers\FunctionMock::$filePutContentsReturnsFalse) {
            return false;
        }

        return \file_put_contents($filename, $data, $flags, ...$args);
    }

    function http_response_code(?int $response_code = null): int|bool
    {
        if ($response_code !== null) {
            return \http_response_code($response_code);
        }

        if (\Jashaics\EnvEncrypter\Tests\Helpers\FunctionMock::$httpResponseCodeGetReturnsFalse) {
            return false;
        }

        return \http_response_code();
    }
}

namespace Jashaics\EnvEncrypter\Console\Commands {
    function openssl_cipher_iv_length(string $cipher): int|false
    {
        if (\Jashaics\EnvEncrypter\Tests\Helpers\FunctionMock::$opensslCipherIvLengthCommandReturnsFalse) {
            return false;
        }

        return \openssl_cipher_iv_length($cipher);
    }

    function file_exists(string $filename): bool
    {
        if (\Jashaics\EnvEncrypter\Tests\Helpers\FunctionMock::$fileExistsReturns !== null) {
            return \Jashaics\EnvEncrypter\Tests\Helpers\FunctionMock::$fileExistsReturns;
        }

        return \file_exists($filename);
    }

    function base64_decode(string $string, bool $strict = false): string|false
    {
        if (\Jashaics\EnvEncrypter\Tests\Helpers\FunctionMock::$base64DecodeCommandReturnsFalse) {
            return false;
        }

        return \base64_decode($string, $strict);
    }
}

namespace Jashaics\EnvEncrypter\Services {
    function openssl_cipher_iv_length(string $cipher): int|false
    {
        if (\Jashaics\EnvEncrypter\Tests\Helpers\FunctionMock::$opensslCipherIvLengthReturnsFalse) {
            return false;
        }

        return \openssl_cipher_iv_length($cipher);
    }

    function base64_decode(string $string, bool $strict = false): string|false
    {
        if (\Jashaics\EnvEncrypter\Tests\Helpers\FunctionMock::$base64DecodeReturnsFalse) {
            return false;
        }

        return \base64_decode($string, $strict);
    }
}
