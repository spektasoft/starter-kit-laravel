<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithConsoleEvents;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class GenerateApiKeysTest extends TestCase
{
    use RefreshDatabase;
    use WithConsoleEvents;

    protected string $envPath;

    protected ?string $originalEnvContent = null;

    protected string $backupEnvPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->envPath = base_path('.env');
        $this->backupEnvPath = base_path('.env.backup');

        // Backup the original .env file if it exists
        if (File::exists($this->envPath)) {
            $this->originalEnvContent = File::get($this->envPath);
            File::move($this->envPath, $this->backupEnvPath);
        }

        // Create a fresh .env file for testing
        File::put($this->envPath, '');

        // Ensure the application reloads environment variables for each test
        // This is crucial because the command modifies the .env file directly.
        $this->app->bootstrapWith([
            \Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables::class,
            \Illuminate\Foundation\Bootstrap\LoadConfiguration::class,
        ]);
    }

    protected function tearDown(): void
    {
        // Clean up the test .env file
        if (File::exists($this->envPath)) {
            File::delete($this->envPath);
        }

        // Restore the original .env file if it was backed up
        if (File::exists($this->backupEnvPath)) {
            File::move($this->backupEnvPath, $this->envPath);
        } elseif ($this->originalEnvContent !== null) {
            // If original .env didn't exist but we had content (e.g., from .env.example)
            // this case might not be strictly necessary if .env.backup handles all.
            // Keeping it for robustness, though it might be redundant.
            File::put($this->envPath, $this->originalEnvContent);
        }

        parent::tearDown();
    }

    public function test_key_generation_when_env_is_empty(): void
    {
        // Ensure the .env file is empty for this test
        File::put($this->envPath, '');

        /** @var \Illuminate\Testing\PendingCommand $command */
        $command = $this->artisan('api-key:generate');
        $command->assertExitCode(0);
        $command->execute();

        $this->assertStringContainsString('API_KEY=', File::get($this->envPath));
    }

    public function test_key_generation_when_api_key_is_not_present(): void
    {
        // Simulate an .env file with other content but no API_KEY
        File::put($this->envPath, "APP_NAME=Laravel\nAPP_ENV=local\n");

        /** @var \Illuminate\Testing\PendingCommand $command */
        $command = $this->artisan('api-key:generate');
        $command->assertExitCode(0);
        $command->execute();

        $content = File::get($this->envPath);
        $this->assertStringContainsString('APP_NAME=Laravel', $content);
        $this->assertStringContainsString('API_KEY=', $content);
    }

    public function test_key_generation_when_api_key_is_present_replaces_existing_key(): void
    {
        // Simulate an .env file with an existing API_KEY
        File::put($this->envPath, "API_KEY=old_key\n");

        // Mock the confirmation to 'yes'
        /** @var \Illuminate\Testing\PendingCommand $command */
        $command = $this->artisan('api-key:generate');
        $command->expectsQuestion('An API key already exists. Do you want to overwrite it?', 'yes')
            ->assertExitCode(0);
        $command->execute();

        $content = File::get($this->envPath);
        $this->assertStringNotContainsString('API_KEY=old_key', $content);
        $this->assertStringContainsString('API_KEY=', $content);
    }

    public function test_confirmation_prompt_confirms_overwrite(): void
    {
        File::put($this->envPath, "API_KEY=old_key\n");

        /** @var \Illuminate\Testing\PendingCommand $command */
        $command = $this->artisan('api-key:generate');
        $command->expectsQuestion('An API key already exists. Do you want to overwrite it?', 'yes')
            ->expectsOutput('API key generated successfully.')
            ->assertExitCode(0);
        $command->execute();

        $content = File::get($this->envPath);
        $this->assertStringNotContainsString('API_KEY=old_key', $content);
        $this->assertStringContainsString('API_KEY=', $content);
    }

    public function test_confirmation_prompt_denies_overwrite(): void
    {
        File::put($this->envPath, "API_KEY=old_key\n");

        /** @var \Illuminate\Testing\PendingCommand $command */
        $command = $this->artisan('api-key:generate');
        $command->expectsQuestion('An API key already exists. Do you want to overwrite it?', false)
            ->expectsOutput('API key generation cancelled.')
            ->assertExitCode(0); // Assuming 0 exit code for cancellation, adjust if needed

        $content = File::get($this->envPath);
        $this->assertStringContainsString('API_KEY=old_key', $content);
    }
}
