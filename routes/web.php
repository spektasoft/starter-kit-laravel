<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::group(['middleware' => ['auth:sanctum', 'json']], function () {
    require __DIR__.'/resources/user.php';
});
