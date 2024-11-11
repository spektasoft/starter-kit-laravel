<?php

use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/user', [UserController::class, 'me'])->name('user');
Route::get('/user/permissions', [UserController::class, 'myPermissions'])->name('user.permissions');
Route::resource('/users', UserController::class);
