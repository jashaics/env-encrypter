<?php

namespace Jashaics\EnvEncrypter\Console\Commands;

use Illuminate\Console\Command;

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
     * 
     * @var string
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
     * @return void
     */
    public function handle()
    {
        // setting source filename
        $sourcefilename = $this->defineEncryptedFilename($this->option('source'));

        // setting destination filename
        $destinationfilename = $this->defineClearFilename($this->option('destination'), $sourcefilename);

        // setting key
        $key = $this->defineKey($this->option('key'));

        // fetching file content
        $data = file_get_contents($sourcefilename);

        $decryptedData = $this->decryptData($data, $key);

        if (false === $decryptedData) {
            $this->error(__('env-encrypter::errors.decryption_fail'));
            exit;
        }

        // encrypting content: if everything works fine deleting backup
        file_put_contents($destinationfilename, $decryptedData, LOCK_EX);

        $this->info(__('env-encrypter::questions.'.$this->action.'.conclusion', ['source' => $sourcefilename, 'destination' => $destinationfilename]));
    }
}
