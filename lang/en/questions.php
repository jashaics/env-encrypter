<?php

return [
    'encrypt' => [
        'clear_filename' => 'What is the name of the file to encrypt? ',
        'encrypted_filename' => 'Set encrypted file name (.encrypted will be automatically appended if not present appended)',
        'key' => 'Set your encryption key (min length :minlength characters long)',
        'overwrite_file' => ':filename already exists; do you want to overwrite it?',
        'conclusion' => 'File :source has been encrypted to :destination; store your key securely otherwise you will not be able to decrypt it',
    ],
    'decrypt' => [
        'encrypted_filename' => 'What is the name of the file to decrypt? ',
        'clear_filename' => 'Set decrypted file name',
        'key' => 'Please insert decryption key',
        'overwrite_file' => ':filename already exists; do you want to overwrite it?',
        'conclusion' => 'File :source has been decrypted to :destination.',
    ],
];