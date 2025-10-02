<?php

namespace Jashaics\EnvEncrypter\Console\Commands;

use Exception;
use Illuminate\Console\Command;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

/**
 * Encrypts the given file using the given key to a given file name with ".encrypted" extention; i.e.: .env.production source file can be encrypted to .env.prod.encrypted
 *
 * @author Jacopo Viscuso <me@jacopoviscuso.it>
 */
class Encrypt extends Command
{
    use EncryptionTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'env-encrypter:encrypt
    {--source= : sets env file to encrypt}
    {--destination= : sets encrypted file name}
    {--key= : sets the used key}
    {--force : overwrites encrypted file with the same name without asking in production }
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Encrypts the given file using the given key to a given file name with ".encrypted" extention; i.e.: .env.production source file can be encrypted to .env.prod.encrypted';

    /**
     * Action to perform
     */
    protected string $action = 'encrypt';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the command.
     *
     * @return int
     */
    public function handle(): int
    {
        if(!is_string($this->option('source')) || trim($this->option('source')) === '') {
            $message = __('env-encrypter::errors.source_option_required');
            error(is_string($message) ? $message : 'Source option required');
            return Command::FAILURE;
        }

        if(!is_string($this->option('destination')) || trim($this->option('destination')) === '') {
            $message = __('env-encrypter::errors.destination_option_required');
            error(is_string($message) ? $message : 'Destination option required');
            return Command::FAILURE;
        }

        if(!is_string($this->option('key')) || trim($this->option('key')) === '') {
            $message = __('env-encrypter::errors.key_option_required');
            error(is_string($message) ? $message : 'Key option required');
            return Command::FAILURE;
        }

        // setting source filename
        $sourcefilename = $this->defineClearFilename($this->option('source'));

        // setting destination filename
        $destinationfilename = $this->defineEncryptedFilename($this->option('destination'), $sourcefilename);

        // setting key
        $key = $this->defineKey($this->option('key'));

        // fetching file content
        $data = file_get_contents($sourcefilename);

        if ($data === false) {
            $message = __('env-encrypter::errors.file_read_fail', ['name' => $sourcefilename]);
            error(is_string($message) ? $message : 'Failed to read file');
            exit;
        }

        // creating a backup of source file
        $backup = null;
        $k = 0;
        while ($backup === null) {
            $backup = $sourcefilename.($k++ > 0 ? $k : '').'.backup';
            if (file_exists($backup)) {
                $backup = null;
            }
        }
        file_put_contents($backup, $data, LOCK_EX);

        try {
            $encryptedData = $this->encryptData($data, $key);
        } catch (Exception $e) {
            error($e->getMessage());
            return Command::FAILURE;
        }

        // encrypting content: if everything works fine deleting backup
        if (file_put_contents($destinationfilename, $encryptedData, LOCK_EX)) {
            unlink($backup);
        }

        $message = __('env-encrypter::questions.'.$this->action.'.conclusion', ['source' => $sourcefilename, 'destination' => $destinationfilename]);
        info(is_string($message) ? $message : '');

        return Command::SUCCESS;
    }
}
