<?php

namespace Tests\Feature\Http\Controllers\Api\V1;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ArtisanControllerTest extends TestCase
{
    use RefreshDatabase;

    protected string $envPath;

    protected function setUp(): void
    {
        parent::setUp();

        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR
            .'artisan-controller-'.bin2hex(random_bytes(16));

        $this->assertTrue(mkdir($directory, 0700));

        $this->beforeApplicationDestroyed(
            static fn () => (new Filesystem)->deleteDirectory($directory)
        );

        $this->app->useEnvironmentPath($directory);
        $this->app->loadEnvironmentFrom('.env.controller-test');

        $this->envPath = $this->app->environmentFilePath();

        $content = 'APP_KEY='.Config::string('app.key')."\n"
            ."FIXTURE_SENTINEL=preserved\n";

        $this->assertSame(strlen($content), file_put_contents($this->envPath, $content));
    }

    public function test_should_fail_if_config_is_disabled(): void
    {
        config(['api.artisan' => false]);
        $response = $this->postJson(route('api.v1.artisan.key.generate'), [
            'api_key' => config('api.key'),
        ]);

        $response->assertMethodNotAllowed();
    }

    public function test_should_fail_if_api_key_is_invalid(): void
    {
        config(['api.artisan' => true]);
        $response = $this->postJson(route('api.v1.artisan.key.generate'), [
            'api_key' => 'wrong-api-key',
        ]);

        $response->assertUnauthorized();
    }

    public function test_key_generate_should_success(): void
    {
        $originalContent = file_get_contents($this->envPath);

        config(['api.artisan' => true]);
        $response = $this->postJson(route('api.v1.artisan.key.generate'), [
            'api_key' => config('api.key'),
        ]);

        $response->assertSuccessful();

        $content = file_get_contents($this->envPath);

        $this->assertNotSame($originalContent, $content);
        $this->assertSame(
            'APP_KEY='.Config::string('app.key')."\n"
                ."FIXTURE_SENTINEL=preserved\n",
            $content
        );
        $this->assertMatchesRegularExpression(
            '/\AAPP_KEY=base64:[A-Za-z0-9+\/]+={0,2}\n'
                .'FIXTURE_SENTINEL=preserved\n\z/',
            $content
        );
    }

    public function test_migrate_should_success(): void
    {
        config(['api.artisan' => true]);
        $response = $this->postJson(route('api.v1.artisan.migrate'), [
            'api_key' => config('api.key'),
        ]);

        $response->assertSuccessful();
    }

    public function test_storage_link_should_success(): void
    {
        config(['api.artisan' => true]);
        $response = $this->postJson(route('api.v1.artisan.storage.link'), [
            'api_key' => config('api.key'),
        ]);

        $response->assertSuccessful();
    }

    public function test_throttle_should_limit_requests(): void
    {
        config(['api.artisan' => true]);

        /** @var int */
        $limit = config('api.limit_per_minute', 5);

        foreach (range(0, $limit) as $i) {
            $response = $this->postJson(route('api.v1.artisan.key.generate'), [
                'api_key' => config('api.key'),
            ]);

            if ($i < $limit) {
                $response->assertSuccessful();
            } else {
                $response->assertTooManyRequests();
            }
        }
    }
}
