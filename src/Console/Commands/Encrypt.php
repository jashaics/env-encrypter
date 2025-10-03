<?php

namespace Jashaics\EnvEncrypter\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

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
        $this->info('Starting encryption...');
        $source = !is_string($this->option('source')) || trim($this->option('source')) === '' ? '' : $this->option('source');
        $destination = !is_string($this->option('destination')) || trim($this->option('destination')) === '' ? '' : $this->option('destination');
        $key = !is_string($this->option('key')) || trim($this->option('key')) === '' ? '' : $this->option('key');

        // setting source filename
        $sourcefilename = $this->defineClearFilename($source);
        $this->info('Source file: '.$sourcefilename);

        // setting destination filename
        $destinationfilename = $this->defineEncryptedFilename($destination, $sourcefilename);
        $this->info('Destination file: '.$destinationfilename);

        // setting key
        $key = $this->defineKey($key);
        $this->info('Using key: '.substr($key, 0, 4).'...'.substr($key, -4).' ('.strlen($key).' chars)');

        // fetching file content
        $data = File::get($sourcefilename);
        $this->info('Fetched file content from: '.$sourcefilename);

        // creating a backup of source file
        $backup = null;
        $k = 0;
        while ($backup === null) {
            $backup = $sourcefilename.($k++ > 0 ? $k : '').'.backup';
            if (file_exists($backup)) {
                $backup = null;
            }
        }
        $success = File::put($backup, $data, true);
        if(false === $success){
            $message = __('env-encrypter::errors.backup_write_fail', ['name' => $backup]);
            error(is_string($message) ? $message : 'Failed to write backup');
        }
        $this->info('Created backup of source file: '.$backup);

        try {
            $encryptedData = $this->encryptData($data, $key);
            $this->info('Encrypted data successfully.');
        } catch (Exception $e) {
            error($e->getMessage());
            return Command::FAILURE;
        }

        // encrypting content: if everything works fine deleting backup
        $success = File::put($destinationfilename, $encryptedData, true);
        if(false === $success){
            $message = __('env-encrypter::errors.file_write_fail', ['name' => $destinationfilename]);
            error(is_string($message) ? $message : 'Failed to write file');
            return Command::FAILURE;
        }
        File::chmod($destinationfilename, 0775 & ~umask());

        unlink($backup);
        $this->info('Wrote encrypted data to: '.$destinationfilename);
        $this->info('Deleted backup file: '.$backup);

        $message = __('env-encrypter::questions.'.$this->action.'.conclusion', ['source' => $sourcefilename, 'destination' => $destinationfilename]);
        info(is_string($message) ? $message : '');
        $this->info('Encryption process completed.');

        return Command::SUCCESS;
    }
}
