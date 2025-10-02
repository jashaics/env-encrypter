<?php

namespace Jashaics\EnvEncrypter\Console\Commands;

use Exception;
use Illuminate\Support\Str;

use function Laravel\Prompts\alert;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\password;
use function Laravel\Prompts\suggest;

/**
 * Common methods for encryption and decryption
 *
 * @author Jacopo Viscuso <me@jacopoviscuso.it>
 */
trait EncryptionTrait
{
    /**
     * Minimum encryption key length
     */
    protected int $min_key_length = 20;

    /**
     * Defining a proper filename
     *
     * @param ?string $filename
     * @param ?string $encryptedFileName
     * @return string valid filename
     */
    protected function defineClearFilename(?string $filename, ?string $encryptedFileName = null): string
    {
        // by default filename is valid
        $valid = true;

        // if filename otherwise is empty string
        $filename = $filename ?? '';
        $encryptedFileName = $encryptedFileName ?? '';

        // if there is a name then valid for now
        $valid = $this->hasName($filename);

        // checking for forbidden characters
        if ($valid === true) {
            $valid = $this->hasforbiddenCharacters($filename);
        }

        // checking if source file has valid name
        if ($valid === true) {
            $valid = $this->hasEnvInName($filename);
        }

        // checking if source file starts with dot
        if ($valid === true) {
            if (! $this->startsWithDot($filename)) {
                $filename = '.'.$filename;
            }
        }

        if ($valid === true) {
            switch ($this->action) {
                case 'encrypt':
                    $valid = $this->hasFile($filename);
                    break;

                case 'decrypt':
                    $valid = ! $this->hasFile($filename, true);

                    // if there is already an encrypted file with same filename ask for overwriting
                    if (! $valid && (! app()->isProduction() || ! $this->option('force'))) {
                        $valid = (bool) confirm(__('env-encrypter::questions.'.$this->action.'.overwrite_file', ['filename' => $filename]));
                    }
                    break;
            }
        }

        // if everything is ok return filename, otherwise ask for filename again
        if ($valid === true) {
            return $filename;
        } else {
            $encryptedFileName = preg_replace('/\.encrypted$/', '', $encryptedFileName);
            $encryptedFileName = $encryptedFileName ?? '';
            $clearFileName = (bool) $encryptedFileName
                        // setting the name of the file after decryption
                        ? suggest(
                            label: __('env-encrypter::questions.'.$this->action.'.clear_filename'),
                            options: [$encryptedFileName],
                            validate: fn ($value) => match (true) {
                                preg_match('/[^\w\d\.\-_]+|\.{2,}/', $value) => __('env-encrypter::errors.characters_not_allowed'),
                                ! preg_match('/\.env/', $value) => __('env-encrypter::errors.filename'),
                                default => null
                            }
                        )
                        // setting the name of the file to encrypt
                        : suggest(
                            label: __('env-encrypter::questions.'.$this->action.'.clear_filename'),
                            options: function ($value) {
                                // getting files .env in the root directory
                                return collect(glob('./.e*'))->map(fn ($file) => basename($file))->filter(fn ($file) => Str::contains($file, $value, ignoreCase: true));
                            },
                            transform: fn (string $value) => ! $this->startsWithDot($value) ? '.'.$value : $value,
                            validate: fn ($value) => match (true) {
                                preg_match('/[^\w\d\.\-_]+|\.{2,}/', $value) => __('env-encrypter::errors.characters_not_allowed'),
                                ! preg_match('/\.env/', $value) => __('env-encrypter::errors.filename'),
                                default => null
                            }
                        );

            return $this->defineClearFilename($clearFileName, $encryptedFileName);
        }
    }

    /**
     * Defining a proper encrypted filename
     *
     * @param ?string $filename
     * @param ?string $clearFileName
     * @return string valid filename
     */
    protected function defineEncryptedFilename(?string $filename, ?string $clearFileName = null): string
    {
        $valid = true;
        $filename = $filename ?? '';

        $valid = $this->hasName($filename);

        // adding encrypted at the end
        if ($valid && $this->action == 'encrypt' && ! preg_match('/\.encrypted$/', $filename)) {
            $filename .= '.encrypted';
        }

        // verifico se ha caratteri non validi
        if ($valid === true) {
            $valid = $this->hasforbiddenCharacters($filename);
        }

        // verifico se contiene env come nome file
        if ($valid === true) {
            $valid = $this->hasEnvInName($filename);
        }

        // verifico se inizia con il .
        if ($valid === true) {
            if (! $this->startsWithDot($filename)) {
                $filename = '.'.$filename;
            }
        }

        if ($valid === true) {
            switch ($this->action) {
                case 'decrypt':
                    $valid = $this->hasFile($filename);
                    break;

                case 'encrypt':
                    $valid = ! $this->hasFile($filename, true);

                    // if there is already an encrypted file with same filename ask for overwriting
                    if (! $valid && (! app()->isProduction() || ! $this->options('force'))) {
                        $valid = (bool) confirm(__('env-encrypter::questions.'.$this->action.'.overwrite_file', ['filename' => $filename]));

                        if ($valid === false) {
                            alert(__('env-encrypter::errors.prompted_for_file_name'));
                        }
                    }
                    break;
            }
        }

        if ($valid === true) {
            return $filename;
        } else {
            $response = (bool) $clearFileName
                        // setting the name of the file after encryption
                        ? suggest(
                            label: __('env-encrypter::questions.'.$this->action.'.encrypted_filename'),
                            options: [$clearFileName.'.encrypted'],
                            validate: fn ($value) => match (true) {
                                preg_match('/[^\w\d\.\-_]+|\.{2,}/', $value) => __('env-encrypter::errors.characters_not_allowed'),
                                ! preg_match('/\.env/', $value) => __('env-encrypter::errors.filename'),
                                default => null
                            }
                        )
                        // setting the name of the file to decrypt
                        : suggest(
                            label: __('env-encrypter::questions.'.$this->action.'.encrypted_filename'),
                            options: function ($value) {
                                // getting files .env in the root directory
                                return collect(glob('./.e*.encrypted'))->map(fn ($file) => basename($file))->filter(fn ($file) => Str::contains($file, $value, ignoreCase: true));
                            },
                            validate: fn ($value) => match (true) {
                                preg_match('/[^\w\d\.\-_]+|\.{2,}/', $value) => __('env-encrypter::errors.characters_not_allowed'),
                                ! preg_match('/\.env/', $value) => __('env-encrypter::errors.filename'),
                                default => null
                            }
                        );

            return $this->defineEncryptedFilename($response, $clearFileName);
        }
    }

    /**
     * Defining a proper encryption key
     *
     * @param ?string $key
     * @return string valid key
     */
    protected function defineKey(?string $key): string
    {
        $valid = true;
        $key = $key ?? '';

        $valid = $this->hasName($key);

        if ($valid === true && $this->action === 'encrypt') {
            $valid = strlen($key) >= $this->min_key_length;
            if (! $valid) {
                $message = __('env-encrypter::errors.key_min_length', ['minlength' => $this->min_key_length]);
                error(is_string($message) ? $message : 'Key too short');
            }
        }

        if ($valid === true) {
            return $key ?? '';
        } else {
            return $this->defineKey(password(
                label: __('env-encrypter::questions.'.$this->action.'.key', ['minlength' => $this->min_key_length]),
                validate: ['password' => 'min:'.$this->min_key_length]
            ));
        }
    }

    /**
     * Generating encrypted data
     *
     * @param string $data in plain text
     * @param string $key
     * @return string encrypted plain text
     * @throws Exception
     */
    protected function encryptData(string $data, string $key)
    {
        $cipher = 'aes-256-cbc';

        $ivlen = openssl_cipher_iv_length($cipher);
        if(false == $ivlen){
            throw new Exception(trans('env-encrypter::errors.invalid_iv_length'));
        }
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
     * @throws Exception
     */
    protected function decryptData(string $encryptedData, string $key): string
    {
        $cipher = 'aes-256-cbc';

        $ivlen = openssl_cipher_iv_length($cipher);

        if(false == $ivlen){
            throw new Exception(trans('env-encrypter::errors.invalid_iv_length'));
        }

        $base64EncryptedData = base64_decode($encryptedData);

        if(false == $base64EncryptedData){
            throw new Exception(trans('env-encrypter::errors.invalid_base64'));
        }

        $EncryptedContent = mb_substr($base64EncryptedData, $ivlen, null, '8bit');

        $iv = mb_substr($base64EncryptedData, 0, $ivlen, '8bit');

        $decrypted = openssl_decrypt($EncryptedContent, $cipher, base64_encode($key), 0, $iv);

        if(false === $decrypted){
            throw new Exception((string) trans('env-encrypter::errors.decryption_fail'));
        }

        return $decrypted;
    }

    /**
     * VALIDATION
     */

    /**
     * A name has been set?
     *
     * @param ?string $filename
     * @return bool
     */
    private function hasName(?string $filename): bool
    {
        return $filename !== null && ! empty($filename);
    }

    /**
     * filename has forbidden characters?
     * Only letters, numbers, . (max 1 in a row), -, _ are allowed
     *
     * @param string $filename
     * @return bool
     */
    private function hasforbiddenCharacters(string $filename): bool
    {
        if (preg_match('/[^\w\d\.\-_]+|\.{2,}/', $filename)) {
            $message = __('env-encrypter::errors.characters_not_allowed');
            error(is_string($message) ? $message : 'Invalid characters');

            return false;
        }

        return true;
    }

    /**
     * Does the name contains .env?
     *
     * @param string $filename
     * @return bool
     */
    private function hasEnvInName(string $filename): bool
    {
        if (! preg_match('/\.env/', $filename)) {
            $message = __('env-encrypter::errors.filename');
            error(is_string($message) ? $message : 'Invalid filename');

            return false;
        }

        return true;
    }

    /**
     * Does the file exists?
     *
     * @param string $filename
     * @param bool $dont_show_error
     * @return bool
     */
    private function hasFile(string $filename, bool $dont_show_error = false): bool
    {
        if (! file_exists($filename)) {
            if ($dont_show_error !== true) {
                $message = __('env-encrypter::errors.file_not_found', ['name' => $filename]);
                error(is_string($message) ? $message : 'File not found');
            }
            return false;
        }

        return true;
    }

    /**
     * Does the name starts with dot (hidden file)
     *
     * @param string $filename
     * @return bool
     */
    private function startsWithDot(string $filename): bool
    {
        if (! preg_match('/^\./', $filename)) {
            $message = __('env-encrypter::errors.must_start_with_dot');
            error(is_string($message) ? $message : 'Must start with dot');

            return false;
        }

        return true;
    }
}
