<?php

use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/users', [UserController::class, 'index'])->name('api.v1.users.index');
Route::post('/users', [UserController::class, 'store'])->name('api.v1.users.create');
