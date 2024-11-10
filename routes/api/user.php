<?php

use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::name('api.v1.')->group(function () {
    Route::apiResource('/users', UserController::class);
});
