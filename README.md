# Laravel ENV encrypter/decrypter

Small tool to manage your .env file encryption/decryption in Laravel projects.  
The idea is to encrypt as many .env files and decrypt it when needed: so you can store your .env files in your repository without any security issue: one for local development, one for staging and one for production. The access to these files is managed through password policies.  
Moreover using automatic deployment you can decrypt the .env file on the server without any manual operation or any developer knowing password decryption.

## Installation

```
composer require jashaics/env-encrypter
```

## Usage

### Encrypt .env file

```php artisan env-encrypter:encrypt``` then follow the instructions

### Decrypt .env file

```php artisan env-encrypter:decrypt``` then follow the instructions
