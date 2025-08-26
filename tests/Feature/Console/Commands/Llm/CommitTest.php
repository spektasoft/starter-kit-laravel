<?php

namespace Tests\Feature\Console\Commands\Llm;

use App\Console\Commands\Llm\Commit;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\WithConsoleEvents;
use Illuminate\Testing\PendingCommand;
use Mockery;
use Mockery\Expectation;
use Mockery\MockInterface;
use Prism\Prism\Prism;
use Prism\Prism\Testing\TextResponseFake;
use Prism\Prism\ValueObjects\Usage;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class CommitTest extends TestCase
{
    use WithConsoleEvents;

    private string $fakeGitDiff;

    private MockInterface $processMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeGitDiff = 'diff --git a/file.php b/file.php';

        // Create a single, controllable mock for the Commit class
        $this->processMock = Mockery::mock(Process::class);
        $this->app->bind(Process::class, function ($app) {
            return $this->processMock;
        });

        // Fake the Prism API to prevent real calls.
        $fakeResponse = TextResponseFake::make()
            ->withText('Hello from Prism')
            ->withUsage(new Usage(10, 20));
        Prism::fake([$fakeResponse]);

        config([
            'prism.using.provider' => 'openai',
            'prism.using.model' => 'gpt-4',
        ]);
    }

    protected function mockSuccessfulGitDiff(): void
    {
        /** @var Expectation $expectation */
        $expectation = $this->processMock->shouldReceive('run');
        $expectation->once();
        /** @var Expectation $expectation */
        $expectation = $this->processMock->shouldReceive('isSuccessful');
        $expectation->once()->andReturn(true);
        /** @var Expectation $expectation */
        $expectation = $this->processMock->shouldReceive('getOutput');
        $expectation->once()->andReturn($this->fakeGitDiff);
    }

    public function test_aborts_in_production_environment(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        /** @var PendingCommand */
        $command = $this->artisan('llm:commit');
        $command->expectsOutput('This command can only be run in a development environment.')
            ->assertExitCode(Command::FAILURE);
    }

    public function test_aborts_if_git_diff_fails(): void
    {
        // Arrange: Create a mock of the Process class to simulate failure
        /** @var Expectation $expectation */
        $expectation = $this->processMock->shouldReceive('run');
        $expectation->once();

        /** @var Expectation $expectation */
        $expectation = $this->processMock->shouldReceive('isSuccessful');
        $expectation->once()->andReturn(false);

        /** @var Expectation $expectation */
        $expectation = $this->processMock->shouldReceive('getErrorOutput');
        $expectation->once()->andReturn('fatal: not a git repository');

        // Act & Assert
        /** @var PendingCommand */
        $command = $this->artisan('llm:commit');
        $command->expectsOutputToContain('Failed to get git diff: fatal: not a git repository')
            ->assertExitCode(Command::FAILURE);
    }

    public function test_exits_with_a_warning_if_there_are_no_staged_files(): void
    {
        // Arrange: Git diff returns an empty string
        /** @var Expectation $expectation */
        $expectation = $this->processMock->shouldReceive('run');
        $expectation->once();
        /** @var Expectation $expectation */
        $expectation = $this->processMock->shouldReceive('isSuccessful');
        $expectation->once()->andReturn(true);
        /** @var Expectation $expectation */
        $expectation = $this->processMock->shouldReceive('getOutput');
        $expectation->once()->andReturn('');

        // Act & Assert
        /** @var PendingCommand */
        $command = $this->artisan('llm:commit');
        $command->expectsOutputToContain('No staged changes to commit')
            ->assertExitCode(Command::FAILURE);
    }

    public function test_only_generates_and_outputs_the_prompt_with_only_prompt_option(): void
    {
        // Arrange: Git diff returns fake git diff
        $this->mockSuccessfulGitDiff();

        /** @var PendingCommand */
        $command = $this->artisan('llm:commit --only-prompt');
        $command->expectsOutput('Generated Prompt:')
            ->expectsQuestion('Do you want to open this prompt in an editor to make changes?', false)
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_calls_api_and_proposes_commit_message(): void
    {
        // Arrange: Git diff returns fake git diff
        $this->mockSuccessfulGitDiff();

        /** @var PendingCommand */
        $command = $this->artisan('llm:commit');
        $command->expectsOutput('Proposed commit message:')
            ->expectsOutput('Hello from Prism')
            ->expectsQuestion('Do you want to open this commit message in an editor to make changes?', false)
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_editor_interaction_flow_with_only_prompt_and_user_confirms(): void
    {
        // Arrange: Git diff returns fake git diff
        $this->mockSuccessfulGitDiff();

        // Create a partial mock of the command to spy on the protected method
        $commitMock = $this->mock(Commit::class)->makePartial()->shouldAllowMockingProtectedMethods();
        /** @var Expectation $expectation */
        $expectation = $commitMock->shouldReceive('openInEditor');
        $expectation->once();
        /** @var Commit $commitMock */
        $commitMock->__construct();

        // Act & Assert
        /** @var PendingCommand */
        $command = $this->artisan('llm:commit --only-prompt');
        $command
            ->expectsConfirmation('Do you want to open this prompt in an editor to make changes?', 'yes')
            ->expectsQuestion('Please enter the command for your preferred editor (e.g., nano, vim, code --wait):', 'vim')
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_editor_interaction_flow_with_only_prompt_and_editor_option(): void
    {
        // Arrange: Git diff returns fake git diff
        $this->mockSuccessfulGitDiff();

        // Arrange: Mock the Commit command to assert openInEditor is called
        $commitMock = $this->mock(Commit::class)->makePartial()->shouldAllowMockingProtectedMethods();
        /** @var Expectation $expectation */
        $expectation = $commitMock->shouldReceive('openInEditor');
        $expectation->once();
        /** @var Commit $commitMock */
        $commitMock->__construct();

        // Act & Assert
        /** @var PendingCommand */
        $command = $this->artisan('llm:commit --only-prompt --editor=vim');
        $command
            ->doesntExpectOutput('Do you want to open this prompt in an editor to make changes?')
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_standard_flow_opens_editor_on_user_confirmation(): void
    {
        $this->mockSuccessfulGitDiff();

        // Arrange: Mock the Commit command to assert openInEditor is called
        $commitMock = $this->mock(Commit::class)->makePartial()->shouldAllowMockingProtectedMethods();
        /** @var Expectation $expectation */
        $expectation = $commitMock->shouldReceive('openInEditor');
        $expectation->once();
        /** @var Commit $commitMock */
        $commitMock->__construct();

        /** @var PendingCommand */
        $command = $this->artisan('llm:commit');
        $command
            ->expectsConfirmation('Do you want to open this commit message in an editor to make changes?', 'yes')
            ->expectsQuestion('Please enter the command for your preferred editor (e.g., nano, vim, code --wait):', 'vim')
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_standard_flow_does_not_open_editor_if_user_declines(): void
    {
        $this->mockSuccessfulGitDiff();

        // Arrange: Mock the Commit command to assert openInEditor is called
        $commitMock = $this->mock(Commit::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $commitMock->shouldNotReceive('openInEditor');
        /** @var Commit $commitMock */
        $commitMock->__construct();

        /** @var PendingCommand */
        $command = $this->artisan('llm:commit');
        $command->expectsConfirmation('Do you want to open this commit message in an editor to make changes?', 'no')
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_standard_flow_with_editor_flag_opens_editor_directly(): void
    {
        $this->mockSuccessfulGitDiff();

        // Arrange: Mock the Commit command to assert openInEditor is called
        $commitMock = $this->mock(Commit::class)->makePartial()->shouldAllowMockingProtectedMethods();
        /** @var Expectation $expectation */
        $expectation = $commitMock->shouldReceive('openInEditor');
        $expectation->once();
        /** @var Commit $commitMock */
        $commitMock->__construct();

        /** @var PendingCommand */
        $command = $this->artisan('llm:commit', ['--editor' => 'code --wait']);
        $command->doesntExpectOutput('Do you want to open this commit message in an editor to make changes?')
            ->assertSuccessful();
    }
}
