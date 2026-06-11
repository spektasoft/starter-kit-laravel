<?php

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\RobotsMiddleware;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RobotsMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('_test_route', function () {
            return response('OK');
        })->middleware(RobotsMiddleware::class);

        Route::get('admin/_test_route', function () {
            return response('OK');
        })->middleware(RobotsMiddleware::class);
    }

    public function test_indexing_is_allowed_on_general_routes_in_production(): void
    {
        $this->app['config']->set('app.env', 'production');
        $this->app->detectEnvironment(fn () => 'production');

        $this->get('_test_route')
            ->assertHeader('X-Robots-Tag', 'all');
    }

    public function test_indexing_is_disallowed_on_admin_routes_in_production(): void
    {
        $this->app['config']->set('app.env', 'production');
        $this->app->detectEnvironment(fn () => 'production');

        $this->get('admin/_test_route')
            ->assertHeader('X-Robots-Tag', 'none');
    }

    public function test_indexing_is_disallowed_globally_in_non_production_environments(): void
    {
        $this->app['config']->set('app.env', 'staging');
        $this->app->detectEnvironment(fn () => 'staging');

        $this->get('_test_route')
            ->assertHeader('X-Robots-Tag', 'none');

        $this->get('admin/_test_route')
            ->assertHeader('X-Robots-Tag', 'none');
    }
}
