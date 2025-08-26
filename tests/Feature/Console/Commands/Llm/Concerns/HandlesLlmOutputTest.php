<?php

namespace Tests\Unit\Console\Commands\Llm\Concerns;

use App\Console\Commands\Llm\Concerns\HandlesLlmOutput;
use Illuminate\Console\Command;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Mockery;
use Mockery\Expectation;
use Mockery\MockInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * A stub command class that uses the trait we want to test.
 */
class TestCommandUsingTrait extends Command
{
    use HandlesLlmOutput;

    protected $signature = 'test:command';

    // This public method allows us to call the protected trait method from our test.
    public function invokeOutput(string $content, string $type): void
    {
        $this->output($content, $type);
    }
}

class HandlesLlmOutputTest extends TestCase
{
    private TestCommandUsingTrait|MockInterface $command;

    private MockInterface $processMock;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create the instance mock for the Process object.
        // This is what our trait's methods will interact with.
        $this->processMock = Mockery::mock(Process::class);

        // 2. Create a partial mock of our test command.
        /** @var MockInterface */
        $command = Mockery::mock(TestCommandUsingTrait::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->command = $command;

        // 3. Set a mock output object on the command to prevent "writeln() on null" errors.
        /** @var MockInterface */
        $output = Mockery::mock(OutputStyle::class);

        /** @var Expectation */
        $expectation = $output->shouldReceive('writeln');
        $expectation->withAnyArgs()->byDefault(); // Accept any output calls

        /** @var TestCommandUsingTrait */
        $command = $this->command;

        /** @var OutputStyle $output */
        $command->setOutput($output);

        // Mock the File facade
        File::shouldReceive('put')->byDefault();
        File::shouldReceive('delete')->byDefault();

        // Use Laravel's built-in testing helper to control UUID generation
        Str::createUuidsUsing(fn () => Uuid::fromString('a1a1a1a1-b2b2-c3c3-d4d4-e5e5e5e5e5e5'));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Str::createUuidsNormally(); // Reset the UUID factory after the test
    }

    public function test_opens_editor_when_editor_option_is_provided(): void
    {
        $editor = 'vim';
        $content = 'some code';
        $type = 'test';
        $tempFilePath = sys_get_temp_dir().'/llm_test_a1a1a1a1-b2b2-c3c3-d4d4-e5e5e5e5e5e5.md';

        /** @var MockInterface */
        $command = $this->command;

        /** @var Expectation */
        $expectation = $command->shouldReceive('option');
        $expectation->with('editor')->once()->andReturn($editor);
        $command->shouldNotReceive('confirm');
        $command->shouldNotReceive('ask');

        /** @var Expectation */
        $expectation = $command->shouldReceive('info');
        $expectation->once()->with("Opening {$type} in editor: '{$editor} {$tempFilePath}'");

        File::shouldReceive('put')->once()->with($tempFilePath, $content);
        File::shouldReceive('delete')->once()->with($tempFilePath);

        /** @var Expectation */
        $expectation = $command->shouldReceive('makeProcess');
        $expectation->with("{$editor} ".escapeshellarg($tempFilePath))
            ->once()
            ->andReturn($this->processMock);

        /** @var Expectation */
        $expectation = $this->processMock->shouldReceive('setTimeout');
        $expectation->with(null)->once();

        /** @var Expectation */
        $expectation = $this->processMock->shouldReceive('setTty');
        $expectation->once()->andReturnSelf();

        /** @var Expectation */
        $expectation = $this->processMock->shouldReceive('run');
        $expectation->once()->andReturn(0); // Simulate a successful exit

        /** @var Expectation */
        $expectation = $this->processMock->shouldReceive('isSuccessful');
        $expectation->once()->andReturn(true);

        /** @var TestCommandUsingTrait */
        $command = $this->command;
        $command->invokeOutput($content, $type);
    }

    public function test_prompts_for_editor_when_option_is_not_provided_and_user_confirms(): void
    {
        $editor = 'nano';
        $content = 'some other code';
        $type = 'class';
        $tempFilePath = sys_get_temp_dir().'/llm_class_a1a1a1a1-b2b2-c3c3-d4d4-e5e5e5e5e5e5.md';

        /** @var MockInterface */
        $command = $this->command;

        /** @var Expectation */
        $expectation = $command->shouldReceive('option');
        $expectation->with('editor')->once()->andReturn(null);

        /** @var Expectation */
        $expectation = $command->shouldReceive('confirm');
        $expectation->once()->andReturn(true);

        /** @var Expectation */
        $expectation = $command->shouldReceive('ask');
        $expectation->once()->andReturn($editor);

        /** @var Expectation */
        $expectation = $command->shouldReceive('info');
        $expectation->once()->with("Opening {$type} in editor: '{$editor} {$tempFilePath}'");

        File::shouldReceive('put')->once()->with($tempFilePath, $content);
        File::shouldReceive('delete')->once()->with($tempFilePath);

        /** @var Expectation */
        $expectation = $command->shouldReceive('makeProcess');
        $expectation->with("{$editor} ".escapeshellarg($tempFilePath))
            ->once()
            ->andReturn($this->processMock);

        /** @var Expectation */
        $expectation = $this->processMock->shouldReceive('setTimeout');
        $expectation->with(null)->once()->andReturnSelf();

        /** @var Expectation */
        $expectation = $this->processMock->shouldReceive('setTty');
        $expectation->once()->andReturnSelf();

        /** @var Expectation */
        $expectation = $this->processMock->shouldReceive('run');
        $expectation->once();

        /** @var Expectation */
        $expectation = $this->processMock->shouldReceive('isSuccessful');
        $expectation->once()->andReturn(true);

        /** @var TestCommandUsingTrait */
        $command = $this->command;
        $command->invokeOutput($content, $type);
    }

    public function test_does_nothing_if_user_declines_to_open_editor(): void
    {
        /** @var MockInterface */
        $command = $this->command;

        /** @var Expectation */
        $expectation = $command->shouldReceive('option');
        $expectation->with('editor')->once()->andReturn(null);

        /** @var Expectation */
        $expectation = $command->shouldReceive('confirm');
        $expectation->with('Do you want to open this test in an editor to make changes?')
            ->once()
            ->andReturn(false);

        $command->shouldNotReceive('ask');
        $command->shouldNotReceive('info');

        File::shouldNotReceive('put'); // @phpstan-ignore-line
        File::shouldNotReceive('delete'); // @phpstan-ignore-line

        $this->processMock->shouldNotReceive('makeProcess');

        /** @var TestCommandUsingTrait */
        $command = $this->command;
        $command->invokeOutput('content', 'test');
    }

    public function test_warns_and_skips_if_user_provides_no_editor_command(): void
    {
        /** @var MockInterface */
        $command = $this->command;

        /** @var Expectation */
        $expectation = $command->shouldReceive('option');
        $expectation->with('editor')->once()->andReturn(null);

        /** @var Expectation */
        $expectation = $command->shouldReceive('confirm');
        $expectation->once()->andReturn(true);

        /** @var Expectation */
        $expectation = $command->shouldReceive('ask');
        $expectation->once()->andReturn(null); // User just presses enter

        /** @var Expectation */
        $expectation = $command->shouldReceive('warn');
        $expectation->once()->with('No editor command provided. Skipping editor step.');

        $command->shouldNotReceive('info');

        File::shouldNotReceive('put'); // @phpstan-ignore-line
        File::shouldNotReceive('delete'); // @phpstan-ignore-line

        $this->processMock->shouldNotReceive('makeProcess');

        /** @var TestCommandUsingTrait */
        $command = $this->command;
        $command->invokeOutput('content', 'test');
    }

    public function test_handles_a_failed_editor_process_and_cleans_up_file(): void
    {
        /** @var MockInterface */
        $command = $this->command;
        /** @var MockInterface */
        $processMock = $this->processMock;

        $editor = 'code --wait';
        $errorOutput = 'command not found: code';
        $tempFilePath = sys_get_temp_dir().'/llm_test_a1a1a1a1-b2b2-c3c3-d4d4-e5e5e5e5e5e5.md';

        /** @var Expectation */
        $expectation = $command->shouldReceive('option');
        $expectation->with('editor')->once()->andReturn($editor);

        /** @var Expectation */
        $expectation = $command->shouldReceive('error');
        $expectation->once()->with('Editor process failed: '.$errorOutput);

        File::shouldReceive('put')->once();
        File::shouldReceive('delete')->once()->with($tempFilePath); // Crucially, still deletes the file

        /** @var Expectation */
        $expectation = $command->shouldReceive('makeProcess');
        $expectation->with("{$editor} ".escapeshellarg($tempFilePath))
            ->once()
            ->andReturn($processMock);

        /** @var Expectation */
        $expectation = $processMock->shouldReceive('setTimeout');
        $expectation->with(null)->once();

        /** @var Expectation */
        $expectation = $processMock->shouldReceive('setTty');
        $expectation->once()->andReturnSelf();

        /** @var Expectation */
        $expectation = $processMock->shouldReceive('run');
        $expectation->once();

        /** @var Expectation */
        $expectation = $processMock->shouldReceive('isSuccessful');
        $expectation->once()->andReturn(false);

        /** @var Expectation */
        $expectation = $processMock->shouldReceive('getErrorOutput');
        $expectation->once()->andReturn($errorOutput);

        /** @var TestCommandUsingTrait */
        $command = $this->command;
        $command->invokeOutput('content', 'test');
    }

    public function test_handles_an_exception_during_process_execution_and_cleans_up_file(): void
    {
        /** @var MockInterface */
        $command = $this->command;
        /** @var MockInterface */
        $processMock = $this->processMock;

        $editor = 'vim';
        $exceptionMessage = 'Process could not be started.';
        $tempFilePath = sys_get_temp_dir().'/llm_test_a1a1a1a1-b2b2-c3c3-d4d4-e5e5e5e5e5e5.md';

        /** @var Expectation */
        $expectation = $command->shouldReceive('option');
        $expectation->with('editor')->once()->andReturn($editor);

        /** @var Expectation */
        $expectation = $command->shouldReceive('error');
        $expectation->once()->with('Error during editor execution: '.$exceptionMessage);

        File::shouldReceive('put')->once();
        File::shouldReceive('delete')->once()->with($tempFilePath); // Crucially, still deletes the file

        /** @var Expectation */
        $expectation = $command->shouldReceive('makeProcess');
        $expectation->with("{$editor} ".escapeshellarg($tempFilePath))
            ->once()
            ->andReturn($processMock);

        /** @var Expectation */
        $expectation = $processMock->shouldReceive('setTimeout');
        $expectation->with(null)->once();

        /** @var Expectation */
        $expectation = $processMock->shouldReceive('setTty');
        $expectation->once()->andReturnSelf();

        /** @var Expectation */
        $expectation = $processMock->shouldReceive('run');
        $expectation->once()->andThrow(new \Exception($exceptionMessage));
        $processMock->shouldNotReceive('isSuccessful');

        /** @var TestCommandUsingTrait */
        $command = $this->command;
        $command->invokeOutput('content', 'test');
    }
}
