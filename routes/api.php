<?php

use App\Http\Controllers\Api\V1\LoginController;
use App\Http\Controllers\Api\V1\LogoutController;
use App\Http\Controllers\Api\V1\TwoFactorChallengeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\EmailVerificationNotificationController;

Route::group(['prefix' => 'v1'], function () {
    Route::post('/login', LoginController::class)
        ->name('api.v1.login');

    Route::post('/two-factor-challenge', TwoFactorChallengeController::class)
        ->name('api.v1.two-factor-challenge');

    Route::group(['middleware' => 'auth:sanctum'], function () {
        Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
            ->name('api.v1.verification.send');

        Route::get('/user', function (Request $request) {
            return $request->user();
        })->name('api.v1.user');

        Route::post('/logout', LogoutController::class)
            ->name('api.v1.logout');
    });
});
