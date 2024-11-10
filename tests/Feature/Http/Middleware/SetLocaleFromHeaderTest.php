<?php

namespace Tests\Feature\Http\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class SetLocaleFromHeaderTest extends TestCase
{
    use RefreshDatabase;

    public function test_locale_is_set_from_accept_language_header(): void
    {
        $this->withHeaders([
            'Content-Language' => 'id',
        ])->getJson(route('api.v1.users.index'));

        $this->assertEquals('id', App::getLocale());
    }

    public function test_locale_is_default_if_header_is_missing(): void
    {
        $this->getJson(route('api.v1.users.index'));
        $this->assertEquals(config('app.locale'), App::getLocale());
    }

    public function test_locale_is_default_if_header_is_invalid(): void
    {
        config(['app.supported_locales' => ['en', 'fr']]);

        $this->withHeaders([
            'Content-Language' => 'id',
        ])->getJson(route('api.v1.users.index'));

        $this->getJson(route('api.v1.users.index'));
        $this->assertEquals(config('app.locale'), App::getLocale());
    }
}
