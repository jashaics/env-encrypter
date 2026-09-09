# Laravel ENV encrypter/decrypter

Small tool to manage your .env file encryption/decryption in Laravel projects.  
The idea is to encrypt as many .env files and decrypt it when needed: so you can store your .env files in your repository without any security issue: one for local development, one for staging and one for production. The access to these files is managed through password policies.  
Moreover using automatic deployment you can decrypt the .env file on the server without any manual operation or any developer knowing password decryption.

## Installation

In your `composer.json`, append to the `repositories` array the following:

```json
    {
        "type": "vcs",
        "url": "https://github.com/jashaics/env-encrypter.git"
    }
```

If you don't have a `repositories` array, create one like this:
```json
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/jashaics/env-encrypter.git"
        }
    ]
```

Then run the following command:

```
composer require jashaics/env-encrypter
```

## Usage

### Encrypt file

To encrypt your .env file run

```php artisan env-encrypter:encrypt``` then follow the instructions

### Decrypt file

To decrypt an encrypted .env file (`.env.encrypted`) run

```php artisan env-encrypter:decrypt``` then follow the instructions

## Related projects

- [jashaics/env-encrypter-addendum](https://github.com/jashaics/env-encrypter-addendum) — standalone, interactive bash/OpenSSL scripts that do the same encrypt/decrypt job outside of a Laravel/Composer context (e.g. on a bare server, in a CI step, or in a non-PHP project). Same idea, no Composer/Laravel dependency required. `composer require`/`composer update` on this package will also surface it as a suggested package.
