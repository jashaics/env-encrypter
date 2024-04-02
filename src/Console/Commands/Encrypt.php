<?php

namespace Jashaics\EnvEncrypter\Console\Commands;

use Illuminate\Console\Command;

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
    {--source= : set env file to encrypt}
    {--destination= : set encrypted file name}
    {--key= : se the used key}
    {--force : overwrites encrypted file with the same name without asking in production }
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Encrypts the given file using the given key to a given file name with ".encrypted" extention; i.e.: .env.production source file can be encrypted to .env.prod.encrypted';

    /**
     * Action been performing
     */
    protected string $action = 'encrypt';

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // setting source filename
        $sourcefilename = $this->defineClearFilename($this->option('source'));

        // setting destination filename
        $destinationfilename = $this->defineEncryptedFilename($this->option('destination'), $sourcefilename);

        // setting key
        $key = $this->defineKey($this->option('key'));

        // fetching file content
        $data = file_get_contents($sourcefilename);

        // creating a backup of source file
        $backup = null;
        $k = 0;
        while (null === $backup) {
            $backup = $sourcefilename.($k++ > 0 ? $k : '').'.backup';
            if (file_exists($backup)) {
                $backup = null;
            }
        }
        file_put_contents($backup, $data, LOCK_EX);

        $encryptedData = $this->encryptData($data, $key);

        if (false === $encryptedData) {
            $this->error(__('env-encrypter::errors.encryption_fail'));
            exit;
        }

        // encrypting content: if everything works fine deleting backup
        if (file_put_contents($destinationfilename, $encryptedData, LOCK_EX)) {
            unlink($backup);
        }

        $this->info(__('env-encrypter::questions.'.$this->action.'.conclusion', ['source' => $sourcefilename, 'destination' => $destinationfilename]));
    }
}
