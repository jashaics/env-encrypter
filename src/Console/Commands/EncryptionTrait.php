<?php

namespace Jashaics\EnvEncrypter\Console\Commands;

/**
 * Common methods for encryption and decryption
 * 
 * @author Jacopo Viscuso <me@jacopoviscuso.it>
 */
trait EncryptionTrait
{
    /**
     * Minimum encryption key length
     * 
     * @var int
     */
    protected int $min_key_length = 20;

    /**
     * Defining a proper filename
     *
     * @param ?string
     * @param string
     * @return string valid filename
     */
    protected function defineClearFilename(?string $filename, string $encryptedFileName = null): string
    {
        // by default filename is valid
        $valid = true;

        // if filename otherwise is null
        $filename = $filename ?? null;

        // if there is a name then valid for now
        $valid = $this->hasName($filename);

        // checking for forbidden characters
        if (true === $valid) {
            $valid = $this->hasforbiddenCharacters($filename);
        }

        // checking if source file has valid name
        if (true === $valid) {
            $valid = $this->hasEnvInName($filename);
        }

        // checking if source file starts with dot
        if (true === $valid) {
            if (! $this->startsWithDot($filename)) {
                $filename = '.'.$filename;
            }
        }

        if (true === $valid) {
            switch($this->action) {
                case 'encrypt':
                    $valid = $this->hasFile($filename);
                    break;

                case 'decrypt':
                    $valid = ! $this->hasFile($filename, true);

                    // if there is already an encrypted file with same filename ask for overwriting
                    if (! $valid && (! app()->isProduction() || ! $this->options('force'))) {
                        $valid = (bool) $this->confirm(__('env-encrypter::questions.'.$this->action.'.overwrite_file', ['filename' => $filename]));
                    }
                    break;
            }
        }

        // if everything is ok return filename, otherwise ask for filename again
        if (true === $valid) {
            return $filename;
        } else {
            $response = (bool) $encryptedFileName
                        ? $this->anticipate(__('env-encrypter::questions.'.$this->action.'.clear_filename'), [preg_replace('/\.encrypted$/', '', $encryptedFileName)])
                        : $this->anticipate(__('env-encrypter::questions.'.$this->action.'.clear_filename'), ['.env']);

            return $this->defineClearFilename($response, $encryptedFileName);
        }
    }

    /**
     * Defining a proper encrypted filename
     *
     * @param ?string
     * @param ?string
     * @return string valid filename
     */
    protected function defineEncryptedFilename(?string $filename, ?string $clearFileName = null): string
    {
        $valid = true;
        $filename = $filename ?? null;

        $valid = $this->hasName($filename);

        // adding encrypted at the end
        if ($valid && $this->action == 'encrypt' && ! preg_match('/\.encrypted$/', $filename)) {
            $filename .= '.encrypted';
        }

        // verifico se ha caratteri non validi
        if (true === $valid) {
            $valid = $this->hasforbiddenCharacters($filename);
        }

        // verifico se contiene env come nome file
        if (true === $valid) {
            $valid = $this->hasEnvInName($filename);
        }

        // verifico se inizia con il .
        if (true === $valid) {
            if (! $this->startsWithDot($filename)) {
                $filename = '.'.$filename;
            }
        }

        if (true === $valid) {
            switch($this->action) {
                case 'decrypt':
                    $valid = $this->hasFile($filename);
                    break;

                case 'encrypt':
                    $valid = ! $this->hasFile($filename, true);

                    // if there is already an encrypted file with same filename ask for overwriting
                    if (! $valid && (! app()->isProduction() || ! $this->options('force'))) {
                        $valid = (bool) $this->confirm(__('env-encrypter::questions.'.$this->action.'.overwrite_file', ['filename' => $filename]));
                    }
                    break;
            }
        }

        if (true === $valid) {
            return $filename;
        } else {
            $response = (bool) $clearFileName
                        ? $this->anticipate(__('env-encrypter::questions.'.$this->action.'.encrypted_filename'), [$clearFileName.'.encrypted'])
                        : $this->anticipate(__('env-encrypter::questions.'.$this->action.'.encrypted_filename'), ['.env.encrypted']);

            return $this->defineEncryptedFilename($response, $clearFileName);
        }
    }

    /**
     * Defining a proper encryption key
     *
     * @param  string  $key
     * @return string valid key
     */
    protected function defineKey(?string $key): string
    {
        $valid = true;
        $key = $key ?? null;

        $valid = $this->hasName($key);

        if ($valid === true && $this->action === 'encrypt') {
            if (! strlen($key) >= $this->min_key_length) {
                $this->error(__('env-encrypter::errors.key_min_length', ['minlength' => $this->min_key_length]));
                $valid = false;
            }
            $valid = strlen($key) > $this->min_key_length;
        }

        if (true === $valid) {
            return $key;
        } else {
            return $this->defineKey($this->secret(__('env-encrypter::questions.'.$this->action.'.key', ['minlength' => $this->min_key_length])));
        }
    }

    /**
     * Generating encrypted data
     *
     * @param string plain text
     * @param string key
     * @return string encrypted plain text
     */
    protected function encryptData(string $data, string $key)
    {
        $cipher = 'aes-256-cbc';

        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $encrypted = openssl_encrypt($data, $cipher, base64_encode($key), 0, $iv, $tag);

        return base64_encode($iv.$encrypted);
    }

    /**
     * Decrypting data
     * 
     * @param string $encryptedData
     * @param string $key
     * @return string decrypted data
     */
    protected function decryptData(string $encryptedData, string $key)
    {
        $cipher = 'aes-256-cbc';

        $ivlen = openssl_cipher_iv_length($cipher);

        $encryptedData = base64_decode($encryptedData);

        $EncryptedContent = mb_substr($encryptedData, $ivlen, null, '8bit');

        $iv = mb_substr($encryptedData, 0, $ivlen, '8bit');

        return openssl_decrypt($EncryptedContent, $cipher, base64_encode($key), 0, $iv);
    }

    /**
     * VALIDATION
     */

    /**
     * A name has been set?
     *
     * @param string
     * @return bool
     */
    private function hasName(?string $filename): bool
    {
        return null !== $filename && ! empty($filename);
    }

    /**
     * filename has forbidden characters?
     * Only letters, numbers, . (max 1 in a row), -, _ are allowed
     *
     * @param string
     * @return bool
     */
    private function hasforbiddenCharacters(string $filename): bool
    {
        if (preg_match('/[^\w\d\.\-_]+|\.{2,}/', $filename)) {
            $this->error(__('env-encrypter::errors.characters_not_allowed'));

            return false;
        }

        return  true;
    }

    /**
     * Does the name contains .env?
     *
     * @param string
     * @return bool
     */
    private function hasEnvInName(string $filename): bool
    {
        if (! preg_match('/\.env/', $filename)) {
            $this->error(__('env-encrypter::errors.filename'));

            return false;
        }

        return true;
    }

    /**
     * Does the file exists?
     *
     * @param string
     * @param bool show errors?
     * @return bool
     */
    private function hasFile(string $filename, bool $dont_show_error = false): bool
    {
        if (! file_exists($filename)) {
            if ($dont_show_error !== true) {
                $this->error(__('env-encrypter::errors.file_not_found', ['name' => $filename]));
            }

            return false;
        }

        return true;
    }

    /**
     * Does the name starts with dot (hidden file)
     *
     * @param string
     * @return bool
     */
    private function startsWithDot(string $filename): bool
    {
        if (! preg_match('/^\./', $filename)) {
            $this->info(__('env-encrypter::errors.must_start_with_dot'));
        }

        return true;
    }
}
