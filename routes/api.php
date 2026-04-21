<?php

use Illuminate\Support\Facades\Route;
use Jashaics\EnvEncrypter\Http\Controllers\API\EnvEncrypterController;

Route::get('/setup-env', [EnvEncrypterController::class, 'setupEnv'])->name('env-encrypter.setup-env');
