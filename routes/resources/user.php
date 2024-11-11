<?php

use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/user', [UserController::class, 'me'])->name('user');
Route::post('/user/can', [UserController::class, 'can'])->name('user.can');
Route::apiResource('/users', UserController::class);
