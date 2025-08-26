<?php

namespace Tests\Unit\Console\Commands\Llm\Concerns;

use App\Console\Commands\Llm\Concerns\RunsInDevelopment;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\TestCase;
use Mockery;
use Mockery\Expectation;
use Mockery\MockInterface;

// Define a dummy command for testing the trait
class TestableCommand extends Command
{
    use RunsInDevelopment;

    protected $name = 'test:command';

    protected $description = 'Test command for RunsInDevelopment trait.';

    // Expose the protected method for testing
    public function callEnsureDevelopmentEnvironment(): bool
    {
        return $this->ensureDevelopmentEnvironment();
    }
}

class RunsInDevelopmentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Clean up Mockery expectations after each test
        Mockery::close();
    }

    public function test_returns_false_and_shows_error_in_production_environment(): void
    {
        // Set the application environment to 'production' for this test
        $this->app->detectEnvironment(fn () => 'production');

        // Create a mock of the TestableCommand to assert the error method call
        /** @var MockInterface */
        $command = Mockery::mock(TestableCommand::class)->makePartial();

        /** @var Expectation */
        $expectation = $command->shouldReceive('error');
        $expectation->once()
            ->with('This command can only be run in a development environment.');

        // Call the method from the trait
        /** @var TestableCommand $command */
        $result = $command->callEnsureDevelopmentEnvironment();

        // Assert the return value
        $this->assertFalse($result);
    }

    public function test_returns_true_and_shows_no_error_in_non_production_environment(): void
    {
        // Set the application environment to 'local' (or any non-production environment) for this test
        $this->app->detectEnvironment(fn () => 'local');

        // Create a mock of the TestableCommand to ensure the error method is not called
        $command = Mockery::mock(TestableCommand::class)->makePartial();
        $command->shouldNotReceive('error');

        // Call the method from the trait
        /** @var TestableCommand $command */
        $result = $command->callEnsureDevelopmentEnvironment();

        // Assert the return value
        $this->assertTrue($result);
    }
}
