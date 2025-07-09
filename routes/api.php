<?php

use App\Http\Controllers\Api\V1\ArtisanController;
use App\Http\Controllers\Api\V1\LoginController;
use App\Http\Controllers\Api\V1\LogoutController;
use App\Http\Controllers\Api\V1\TwoFactorChallengeController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\EmailVerificationNotificationController;

Route::group(['middleware' => ['json', 'throttle:api'], 'prefix' => 'v1'], function () {
    Route::post('/login', LoginController::class)
        ->name('api.v1.login');

    Route::post('/two-factor-challenge', TwoFactorChallengeController::class)
        ->name('api.v1.two-factor-challenge');

    Route::group(['middleware' => 'auth:sanctum'], function () {
        Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
            ->name('api.v1.verification.send');

        Route::post('/logout', LogoutController::class)
            ->name('api.v1.logout');

        Route::name('api.v1.')->group(function () {
            require __DIR__.'/resources/user.php';
        });
    });

    Route::name('api.v1.')
        ->middleware(['verify.api.artisan', 'verify.api.key'])
        ->group(function () {
            Route::prefix('artisan')
                ->controller(ArtisanController::class)
                ->group(function () {
                    Route::post('key-generate', 'keyGenerate')->name('artisan.key.generate');
                    Route::post('migrate', 'migrate')->name('artisan.migrate');
                    Route::post('optimize', 'optimize')->name('artisan.optimize');
                    Route::post('seed-permissions', 'seedPermissions')->name('artisan.seed.permissions');
                    Route::post('storage-link', 'storageLink')->name('artisan.storage.link');
                });
        });
});
