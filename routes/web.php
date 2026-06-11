<?php

use App\Http\Controllers\PageController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;
use Laravel\Jetstream\Jetstream;

Route::group(['middleware' => ['verified']], function () {
    Route::get('/', [PageController::class, 'index'])->name('home');

    require __DIR__.'/resources/page.php';
});

Route::group(['middleware' => ['auth:sanctum', 'json']], function () {
    require __DIR__.'/resources/user.php';
});

if (Jetstream::hasTermsAndPrivacyPolicyFeature()) {
    Route::get('/terms-of-service', [PageController::class, 'terms'])->name('terms.show');
    Route::get('/privacy-policy', [PageController::class, 'policy'])->name('policy.show');
}

Route::get('/sitemap.xml', [SitemapController::class, 'index']);

Route::get('/robots.txt', function () {
    $isProduction = app()->environment('production');

    if ($isProduction) {
        $content = [
            'User-agent: *',
            'Disallow: /admin',
            '',
            'Sitemap: '.url('sitemap.xml'),
        ];
    } else {
        $content = [
            'User-agent: *',
            'Disallow: /',
        ];
    }

    return response(implode("\n", $content), 200)
        ->header('Content-Type', 'text/plain; charset=UTF-8');
});

Route::fallback([PageController::class, 'fallback']);
