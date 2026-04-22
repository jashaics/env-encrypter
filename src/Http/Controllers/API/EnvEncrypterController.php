<?php

namespace Jashaics\EnvEncrypter\Http\Controllers\API;

use Illuminate\Routing\Controller;
use Exception;
use Jashaics\EnvEncrypter\Services\DecryptService;

class EnvEncrypterController extends Controller
{
    /**
     * Decrypts the .env file and puts the decrypted content into the destination file
     *
     * @return void
     */
    public function setupEnv(): void
    {
        header('Content-Type: application/json');

        try {
            if (!isset($_POST['source']) || !isset($_POST['destination']) || !isset($_POST['key'])) {
                http_response_code(400);
                throw new Exception('Errore nei parametri');
            }

            if (!is_string($_POST['source']) || !is_string($_POST['destination']) || !is_string($_POST['key'])) {
                http_response_code(400);
                throw new Exception('I parametri devono essere stringhe');
            }

            $source = $_POST['source'];

            if (strpos($source, '..') !== false) {
                http_response_code(406);
                throw new Exception('Sorgente non valida');
            }

            $destination = $_POST['destination'];

            if (strpos($destination, '..') !== false) {
                http_response_code(406);
                throw new Exception('Destinazione non valida');
            }

            $key = $_POST['key'];

            $data = file_get_contents('../'.$source);
            if (false === $data) {
                http_response_code(406);
                throw new Exception('Errore nella lettura del file sorgente');
            }

            if (empty($data)) {
                http_response_code(204);
                throw new Exception('File sorgente vuoto');
            }

            $decryptedData = DecryptService::decryptEnv($data, $key);

            // putting decrypted data into destination file
            $success = file_put_contents('../'.$destination, $decryptedData, LOCK_EX);
            if (false === $success) {
                http_response_code(403);
                throw new Exception('Errore nella scrittura del file di destinazione');
            }

            http_response_code(200);
            echo json_encode([
                'error' => false,
                'message' => 'Environment file decrypted successfully'
            ]);

        } catch (Exception $e) {
            $httpCode = http_response_code();
            if (is_bool($httpCode)) {
                $httpCode = 500;
            }
            http_response_code($httpCode ?: 500);

            echo json_encode([
                'error' => true,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'http_code' => $httpCode ?: 500
            ]);
            $this->terminate(1);
        }
    }

    // @codeCoverageIgnoreStart
    // exit() cannot be covered without actually exiting the test process, so we ignore this method in code coverage reports
    protected function terminate(int $code): void
    {
        exit($code);
    }
    // @codeCoverageIgnoreEnd

}
