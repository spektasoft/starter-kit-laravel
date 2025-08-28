<?php

use App\Enums\Page\Status;
use App\Http\Controllers\SitemapController;
use App\Models\Page;
use Illuminate\Support\Facades\Route;
use Laravel\Jetstream\Jetstream;

Route::group(['middleware' => ['verified']], function () {
    Route::get('/', function () {
        return view('welcome');
    })->name('home');

    require __DIR__.'/resources/page.php';
});

Route::group(['middleware' => ['auth:sanctum', 'json']], function () {
    require __DIR__.'/resources/user.php';
});

if (Jetstream::hasTermsAndPrivacyPolicyFeature()) {
    Route::get('/terms-of-service', function () {
        $record = Page::whereStatus(Status::Publish)
            ->find(config('page.terms'));

        return view('terms-of-service', ['record' => $record]);
    })->name('terms.show');
    Route::get('/privacy-policy', function () {
        $record = Page::whereStatus(Status::Publish)
            ->find(config('page.privacy'));

        return view('privacy-policy', ['record' => $record]);
    })->name('policy.show');
}

Route::get('/sitemap.xml', [SitemapController::class, 'index']);

Route::fallback(function () {
    return response()->view('errors.404', [], 404);
});
