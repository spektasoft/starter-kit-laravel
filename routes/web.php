<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('/user', function (Request $request) {
        if ($request->expectsJson()) {
            return response()->json($request->user());
        } else {
            return redirect(route('profile.show'));
        }
    })->name('user');
});
