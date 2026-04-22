<?php

namespace Jashaics\EnvEncrypter\Tests\Helpers;

class FunctionMock
{
    public static bool $opensslCipherIvLengthReturnsFalse = false;
    public static bool $base64DecodeReturnsFalse = false;

    public static bool $fileGetContentsReturnsFalse = false;
    public static ?string $fileGetContentsReturns = null;
    public static bool $filePutContentsReturnsFalse = false;
    public static bool $httpResponseCodeGetReturnsFalse = false;

    public static bool $opensslCipherIvLengthCommandReturnsFalse = false;
    public static ?bool $fileExistsReturns = null;
    public static bool $base64DecodeCommandReturnsFalse = false;

    public static function reset(): void
    {
        self::$opensslCipherIvLengthReturnsFalse = false;
        self::$base64DecodeReturnsFalse = false;
        self::$fileGetContentsReturnsFalse = false;
        self::$fileGetContentsReturns = null;
        self::$filePutContentsReturnsFalse = false;
        self::$httpResponseCodeGetReturnsFalse = false;
        self::$opensslCipherIvLengthCommandReturnsFalse = false;
        self::$fileExistsReturns = null;
        self::$base64DecodeCommandReturnsFalse = false;
    }
}
