<?php

namespace Jashaics\EnvEncrypter\Services;

use Exception;

class DecryptService
{

    /**
     * Decrypts the encrypted data
     *
     * @param string $encryptedData
     * @param string $key
     * @return string
     * @throws Exception
     */
    public static function decryptEnv(string $encryptedData, string $key): string
    {
        $cipher = 'aes-256-cbc';
        $ivlen = openssl_cipher_iv_length($cipher);
        if (false === $ivlen) {
            http_response_code(412);
            throw new Exception('Errore nella lettura del vettore di inizializzazione');
        }
        $encryptedData = base64_decode($encryptedData);
        if (false == $encryptedData) {
            http_response_code(409);
            throw new Exception('Errore nella decodifica base64 dei dati');
        }
        $EncryptedContent = mb_substr($encryptedData, $ivlen, null, '8bit');
        $iv = mb_substr($encryptedData, 0, $ivlen, '8bit');

        $decoded = openssl_decrypt($EncryptedContent, $cipher, base64_encode($key), 0, $iv);
        if (false === $decoded) {
            http_response_code(500);
            throw new Exception('Errore durante la decodifica');
        }
        return $decoded;
    }
}