<?php

namespace Jashaics\EnvEncrypter\Console\Commands;

use Illuminate\Console\Command;

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
        $sourcefilename = $this->defineEncryptedFilename($this->option('source'));

        // setting destination filename
        $destinationfilename = $this->defineClearFilename($this->option('destination'), $sourcefilename);

        // setting key
        $key = $this->defineKey($this->option('key'));

        // fetching file content
        $data = file_get_contents($sourcefilename);

        if ($data === false) {
            $message = __('env-encrypter::errors.file_read_fail', ['name' => $sourcefilename]);
            error(is_string($message) ? $message : 'Failed to read file');
            return Command::FAILURE;
        }

        try {
            $decryptedData = $this->decryptData($data, $key);
        } catch (\Exception $e) {
            error($e->getMessage());
            return Command::FAILURE;
        }

        // encrypting content: if everything works fine deleting backup
        file_put_contents($destinationfilename, $decryptedData, LOCK_EX);

        $message = __('env-encrypter::questions.'.$this->action.'.conclusion', ['source' => $sourcefilename, 'destination' => $destinationfilename]);
        info(is_string($message) ? $message : '');

        return Command::SUCCESS;
    }
}
