<?php

use App\Http\Controllers\API\V1\LoginController;
use App\Http\Controllers\API\V1\LogoutController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1'], function () {
    Route::post('/login', LoginController::class)
        ->name('api.v1.login');

    Route::group(['middleware' => 'auth:sanctum'], function () {
        Route::get('/user', function (Request $request) {
            return $request->user();
        })->name('api.v1.user');

        Route::post('/logout', LogoutController::class)
            ->name('api.v1.logout');
    });
});
