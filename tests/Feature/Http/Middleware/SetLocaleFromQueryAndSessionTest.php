<?php

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\SetLocaleFromQueryAndSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class SetLocaleFromQueryAndSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_locale_is_set_from_query_parameter(): void
    {
        $middleware = new SetLocaleFromQueryAndSession;
        $request = Request::create('/?lang=id');
        $response = $middleware->handle($request, function ($request) {
            return response()->json(['locale' => App::getLocale()]);
        });

        $this->assertEquals('id', App::getLocale());
        $this->assertEquals('id', Session::get('locale'));
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function test_locale_is_set_from_session(): void
    {
        config(['app.supported_locales' => ['en', 'fr']]);
        $middleware = new SetLocaleFromQueryAndSession;
        Session::put('locale', 'fr');
        $request = Request::create('/');
        $response = $middleware->handle($request, function ($request) {
            return response()->json(['locale' => App::getLocale()]);
        });

        $this->assertEquals('fr', App::getLocale());
        $this->assertEquals('fr', Session::get('locale'));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_locale_is_set_to_default_when_invalid(): void
    {
        config(['app.supported_locales' => ['en', 'es']]);
        config(['app.locale' => 'en']);
        $middleware = new SetLocaleFromQueryAndSession;
        $request = Request::create('/?lang=invalid');
        $response = $middleware->handle($request, function ($request) {
            return response()->json(['locale' => App::getLocale()]);
        });

        $this->assertEquals('en', App::getLocale());
        $this->assertEquals('en', Session::get('locale'));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_locale_is_set_to_default_when_no_session_or_query(): void
    {
        config(['app.supported_locales' => ['en', 'ja']]);
        config(['app.locale' => 'en']);
        $middleware = new SetLocaleFromQueryAndSession;
        $request = Request::create('/');
        $response = $middleware->handle($request, function ($request) {
            return response()->json(['locale' => App::getLocale()]);
        });

        $this->assertEquals('en', App::getLocale());
        $this->assertEquals('en', Session::get('locale'));
        $this->assertEquals(200, $response->getStatusCode());
    }
}
