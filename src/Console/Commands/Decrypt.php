<?php

namespace Jashaics\EnvEncrypter\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

/**
 * Decrypts the given source file to the destination file using the given key
 *
 * @author Jacopo Viscuso <me@jacopoviscuso.it>
 */
class Decrypt extends Command
{
    use EncryptionTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'env-encrypter:decrypt
    {--source= : set the file to decrypt}
    {--destination= : set decrypted file name}
    {--key= : se the used key}
    {--force : overwrites decrypted file with the same name without asking in production }
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'decrypts the given source file to the destination file using the given key';

    /**
     * Action to perform
     */
    protected string $action = 'decrypt';

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
        $this->info('Starting decryption...');
        $source = !is_string($this->option('source')) || trim($this->option('source')) === '' ? '' : $this->option('source');
        $destination = !is_string($this->option('destination')) || trim($this->option('destination')) === '' ? '' : $this->option('destination');
        $key = !is_string($this->option('key')) || trim($this->option('key')) === '' ? '' : $this->option('key');


        // setting source filename
        $sourcefilename = $this->defineEncryptedFilename($source);
        $this->info('Source file: '.$sourcefilename);

        // setting destination filename
        $destinationfilename = $this->defineClearFilename($destination, $sourcefilename);
        $this->info('Destination file: '.$destinationfilename);

        // setting key
        $key = $this->defineKey($key);
        $this->info('Using key: '.substr($key, 0, 4).'...'.substr($key, -4).' ('.strlen($key).' chars)');

        // fetching file content
        $data = File::get($sourcefilename);
        $this->info('Fetched file content from: '.$sourcefilename);

        try {
            $decryptedData = $this->decryptData($data, $key);
            $this->info('Decrypted data successfully.');
        } catch (\Exception $e) {
            error($e->getMessage());
            return Command::FAILURE;
        }

        // encrypting content: if everything works fine deleting backup
        $success = File::put($destinationfilename, $decryptedData, true);
        if(false === $success){
            $message = __('env-encrypter::errors.file_write_fail', ['name' => $destinationfilename]);
            error(is_string($message) ? $message : 'Failed to write file');
            return Command::FAILURE;
        }
        File::chmod($destinationfilename, 0775 & ~umask());
        $this->info('Wrote decrypted data to: '.$destinationfilename);

        $message = __('env-encrypter::questions.'.$this->action.'.conclusion', ['source' => $sourcefilename, 'destination' => $destinationfilename]);
        info(is_string($message) ? $message : 'Decryption completed successfully');

        return Command::SUCCESS;
    }
}
