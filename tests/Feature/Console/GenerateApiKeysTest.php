<?php

namespace Tests\Feature\Console;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithConsoleEvents;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\PendingCommand;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class GenerateApiKeysTest extends TestCase
{
    use RefreshDatabase;
    use WithConsoleEvents;

    protected string $envPath;

    protected function setUp(): void
    {
        parent::setUp();

        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR
            .'generate-api-keys-'.bin2hex(random_bytes(16));

        $this->assertTrue(mkdir($directory, 0700));

        $this->beforeApplicationDestroyed(
            static fn () => (new Filesystem)->deleteDirectory($directory)
        );

        $this->app->useEnvironmentPath($directory);
        $this->app->loadEnvironmentFrom('.env.command-test');

        $this->envPath = $this->app->environmentFilePath();

        $this->assertSame(0, File::put($this->envPath, ''));
    }

    public function test_key_generation_when_env_is_empty(): void
    {
        /** @var PendingCommand $command */
        $command = $this->artisan('api-key:generate');
        $command->expectsOutput('API key generated successfully.')
            ->assertExitCode(0);
        $command->execute();

        $this->assertMatchesRegularExpression(
            '/\A\s*API_KEY=[A-Za-z0-9]{32}\z/',
            File::get($this->envPath)
        );
    }

    public function test_key_generation_creates_missing_configured_environment_file(): void
    {
        $this->assertTrue(File::delete($this->envPath));
        $this->assertFileDoesNotExist($this->envPath);

        /** @var PendingCommand $command */
        $command = $this->artisan('api-key:generate');
        $command->expectsOutput('API key generated successfully.')
            ->assertExitCode(0);
        $command->execute();

        $this->assertFileExists($this->envPath);
        $this->assertMatchesRegularExpression(
            '/\A\s*API_KEY=[A-Za-z0-9]{32}\z/',
            File::get($this->envPath)
        );
    }

    public function test_key_generation_when_api_key_is_not_present(): void
    {
        // Simulate an .env file with other content but no API_KEY
        File::put($this->envPath, "APP_NAME=Laravel\nAPP_ENV=local\n");

        /** @var PendingCommand $command */
        $command = $this->artisan('api-key:generate');
        $command->assertExitCode(0);
        $command->execute();

        $content = File::get($this->envPath);
        $this->assertMatchesRegularExpression(
            '/\AAPP_NAME=Laravel\nAPP_ENV=local\n'
                .preg_quote(PHP_EOL, '/')
                .'API_KEY=[A-Za-z0-9]{32}\z/',
            $content
        );
    }

    public function test_key_generation_when_api_key_is_present_replaces_existing_key(): void
    {
        File::put($this->envPath, "APP_NAME=Laravel\nAPI_KEY=old_key\n");

        /** @var PendingCommand $command */
        $command = $this->artisan('api-key:generate');
        $command->expectsQuestion('An API key already exists. Do you want to overwrite it?', true)
            ->assertExitCode(0);
        $command->execute();

        $this->assertMatchesRegularExpression(
            '/\AAPP_NAME=Laravel\nAPI_KEY=[A-Za-z0-9]{32}\n\z/',
            File::get($this->envPath)
        );
    }

    public function test_confirmation_prompt_confirms_overwrite(): void
    {
        File::put($this->envPath, "API_KEY=old_key\n");

        /** @var PendingCommand $command */
        $command = $this->artisan('api-key:generate');
        $command->expectsQuestion('An API key already exists. Do you want to overwrite it?', true)
            ->expectsOutput('API key generated successfully.')
            ->assertExitCode(0);
        $command->execute();

        $content = File::get($this->envPath);
        $this->assertMatchesRegularExpression(
            '/\AAPI_KEY=[A-Za-z0-9]{32}\n\z/',
            $content
        );
    }

    public function test_confirmation_prompt_denies_overwrite(): void
    {
        File::put($this->envPath, "API_KEY=old_key\n");

        /** @var PendingCommand $command */
        $command = $this->artisan('api-key:generate');
        $originalContent = "# Preserve this comment\nAPP_NAME=Laravel\nAPI_KEY=old_key\nAPP_ENV=local\n";
        File::put($this->envPath, $originalContent);

        $command->expectsQuestion('An API key already exists. Do you want to overwrite it?', false)
            ->expectsOutput('API key generation cancelled.')
            ->doesntExpectOutput('API key generated successfully.')
            ->assertExitCode(0);
        $command->execute();

        $this->assertSame($originalContent, File::get($this->envPath));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function readFailures(): array
    {
        return [
            'false result' => ['false'],
            'exception' => ['exception'],
        ];
    }

    #[DataProvider('readFailures')]
    public function test_read_failure_stops_without_writing(string $failure): void
    {
        $originalContent = "APP_NAME=Laravel\nAPI_KEY=old_key\n";
        File::put($this->envPath, $originalContent);

        $filesystem = Mockery::mock(Filesystem::class)->makePartial();
        $read = $filesystem->shouldReceive('get')->with($this->envPath);

        if ($failure === 'exception') {
            $read->andThrow(new RuntimeException('Simulated read failure.'));
        } else {
            $read->andReturn(false);
        }

        $filesystem->shouldNotReceive('put');
        $this->instance(Filesystem::class, $filesystem);

        /** @var PendingCommand $command */
        $command = $this->artisan('api-key:generate');
        $command->expectsOutput('Could not read environment file.')
            ->doesntExpectOutput('API key generated successfully.')
            ->assertExitCode(1);
        $command->execute();

        $this->assertSame($originalContent, File::get($this->envPath));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function writeFailures(): array
    {
        return [
            'false result' => ['false'],
            'exception' => ['exception'],
            'incomplete write' => ['incomplete'],
        ];
    }

    #[DataProvider('writeFailures')]
    public function test_write_failure_returns_failure_without_success_output(string $failure): void
    {
        $originalContent = "APP_NAME=Laravel\nAPP_ENV=local\n";
        File::put($this->envPath, $originalContent);

        $attemptedContent = null;
        $filesystem = Mockery::mock(Filesystem::class)->makePartial();
        $filesystem->shouldReceive('put')
            ->with($this->envPath, Mockery::type('string'))
            ->andReturnUsing(function (string $path, string $content) use ($failure, &$attemptedContent) {
                $attemptedContent = $content;

                if ($failure === 'exception') {
                    throw new RuntimeException('Simulated write failure.');
                }

                if ($failure === 'incomplete') {
                    return file_put_contents($path, substr($content, 0, -1));
                }

                return false;
            });

        $this->instance(Filesystem::class, $filesystem);

        /** @var PendingCommand $command */
        $command = $this->artisan('api-key:generate');
        $command->expectsOutput('Could not write environment file.')
            ->doesntExpectOutput('API key generated successfully.')
            ->assertExitCode(1);
        $command->execute();

        $this->assertIsString($attemptedContent);
        $this->assertMatchesRegularExpression(
            '/\AAPP_NAME=Laravel\nAPP_ENV=local\n'
                .preg_quote(PHP_EOL, '/')
                .'API_KEY=[A-Za-z0-9]{32}\z/',
            $attemptedContent
        );

        $expectedContent = $failure === 'incomplete'
            ? substr($attemptedContent, 0, -1)
            : $originalContent;

        $this->assertSame($expectedContent, File::get($this->envPath));
    }
}
