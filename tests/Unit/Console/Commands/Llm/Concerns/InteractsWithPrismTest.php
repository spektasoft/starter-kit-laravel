<?php

namespace Tests\Unit\Console\Commands\Llm\Concerns;

use App\Console\Commands\Llm\Concerns\HandlesLlmOutput;
use App\Console\Commands\Llm\Concerns\InteractsWithPrism;
use Illuminate\Console\Command;
use Mockery;
use Mockery\Expectation;
use Mockery\MockInterface;
use Prism\Prism\Prism;
use Prism\Prism\Testing\TextResponseFake;
use Prism\Prism\Text\Request;
use Prism\Prism\ValueObjects\Usage;
use Tests\TestCase;

/**
 * A concrete command class for testing the trait's behavior.
 */
class TestCommandForPrism extends Command
{
    use HandlesLlmOutput, InteractsWithPrism;

    protected $signature = 'test:prism-command';

    // The `output` method comes from `HandlesLlmOutput`.
    // We will mock it to verify it's called correctly.
    public function output(string $content, string $type): void
    {
        // Stub implementation for the test class.
    }

    // The `output` method comes from `HandlesLlmOutput`.
    // We will mock it to verify it's called correctly.
    public function invokeGenerateLlmResponse(string $prompt, string $messageType): void
    {
        $this->generateLlmResponse($prompt, $messageType);
    }
}

class InteractsWithPrismTest extends TestCase
{
    private MockInterface|TestCommandForPrism $command;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a partial mock of our test command to mock console methods.
        /** @var MockInterface */
        $command = Mockery::mock(TestCommandForPrism::class)->makePartial();
        $this->command = $command;
        $this->command->shouldAllowMockingProtectedMethods();
    }

    /**
     * @covers \App\Console\Commands\Llm\Concerns\InteractsWithPrism::generateLlmResponse
     */
    public function test_displays_prompt_and_outputs_it_when_only_prompt_option_is_used(): void
    {
        // 1. Arrange
        $prompt = 'This is the test prompt to be displayed.';
        $messageType = 'Commit Message';

        // Set up the fake implementation. We expect no API calls.
        $fake = Prism::fake();

        /** @var MockInterface */
        $command = $this->command;

        /** @var Expectation $expectation */
        $expectation = $command->shouldReceive('option');
        $expectation->with('only-prompt')->once()->andReturn(true);

        /** @var Expectation $expectation */
        $expectation = $command->shouldReceive('info');
        $expectation->with('Generated Prompt:')->once();

        /** @var Expectation $expectation */
        $expectation = $command->shouldReceive('line');
        $expectation->with($prompt)->once();

        /** @var Expectation $expectation */
        $expectation = $command->shouldReceive('output');
        $expectation->with($prompt, 'prompt')->once();

        // 2. Act
        /** @var TestCommandForPrism $command */
        $command->invokeGenerateLlmResponse($prompt, $messageType);

        // 3. Assert
        // Verify that no API call was made to Prism.
        $fake->assertCallCount(0);
    }

    /**
     * @covers \App\Console\Commands\Llm\Concerns\InteractsWithPrism::generateLlmResponse
     */
    public function test_calls_prism_with_the_correct_prompt_and_configuration(): void
    {
        // 1. Arrange
        $prompt = 'Generate a commit message for these changes.';
        $messageType = 'Commit Message';
        $provider = 'openai';
        $model = 'gpt-4-turbo';

        config()->set('prism.using.provider', $provider);
        config()->set('prism.using.model', $model);

        // Prepare a fake response for Prism to return.
        $fakeResponse = TextResponseFake::make()
            ->withText('Test response')
            ->withUsage(new Usage(10, 20));

        $fake = Prism::fake([$fakeResponse]);

        /** @var MockInterface */
        $command = $this->command;

        /** @var Expectation $expectation */
        $expectation = $command->shouldReceive('option');
        $expectation->with('only-prompt')->andReturn(false);

        // Suppress actual console output for this test.
        $command->shouldReceive('newLine', 'info', 'line', 'output');

        // 2. Act
        /** @var TestCommandForPrism $command */
        $command->invokeGenerateLlmResponse($prompt, $messageType);

        // 3. Assert
        // Verify that a text completion request was sent.
        $fake->assertRequest(function ($requests) use ($prompt, $provider, $model) {
            /** @var array<Request> $requests */
            $this->assertSame($prompt, $requests[0]->prompt());
            $this->assertSame($provider, $requests[0]->provider());
            $this->assertSame($model, $requests[0]->model());
        });
    }

    /**
     * @covers \App\Console\Commands\Llm\Concerns\InteractsWithPrism::generateLlmResponse
     */
    public function test_formats_and_displays_the_llm_response_and_stats_correctly(): void
    {
        // 1. Arrange
        $messageType = 'Test Output';
        $llmResponseText = 'This is the generated text from the LLM. '; // Trailing space for trim test
        $promptTokens = 50;
        $completionTokens = 150;

        // Prepare the fake response with specific usage data.
        $fakeResponse = TextResponseFake::make()
            ->withText($llmResponseText)
            ->withUsage(new Usage($promptTokens, $completionTokens));

        Prism::fake([$fakeResponse]);

        /** @var MockInterface */
        $command = $this->command;

        /** @var Expectation $expectation */
        $expectation = $command->shouldReceive('option');
        $expectation->with('only-prompt')->andReturn(false);

        // Capture console output to verify formatting.
        $outputs = [];

        /** @var Expectation $expectation */
        $expectation = $command->shouldReceive('newLine');
        $expectation->once()->andReturnUsing(function () use (&$outputs) {
            $outputs[] = 'NEWLINE';
        });

        /** @var Expectation $expectation */
        $expectation = $command->shouldReceive('info');
        $expectation->andReturnUsing(function (string $msg) use (&$outputs) {
            $outputs[] = "INFO: {$msg}";
        });

        /** @var Expectation $expectation */
        $expectation = $command->shouldReceive('line');
        $expectation->andReturnUsing(function (string $msg) use (&$outputs) {
            $outputs[] = "LINE: {$msg}";
        });

        /** @var Expectation $expectation */
        $expectation = $command->shouldReceive('output'); // Ignore for this test

        // 2. Act
        /** @var TestCommandForPrism $command */
        $command->invokeGenerateLlmResponse('some prompt', $messageType);

        // 3. Assert
        $this->assertContains('NEWLINE', $outputs);
        $this->assertContains("INFO: Proposed {$messageType}:", $outputs);
        $this->assertContains('INFO: ------------------------', $outputs);
        $this->assertContains('LINE: '.trim($llmResponseText), $outputs); // Check if text is trimmed
        $this->assertContains("INFO: Prompt tokens: {$promptTokens}", $outputs);
        $this->assertContains("INFO: Completion tokens: {$completionTokens}", $outputs);
        $this->assertTrue(
            (bool) preg_grep('/^INFO: Elapsed time: \d+(\.\d{1,2})? seconds$/', $outputs),
        );
    }

    /**
     * @covers \App\Console\Commands\Llm\Concerns\InteractsWithPrism::generateLlmResponse
     */
    public function test_calls_the_output_method_with_the_trimmed_llm_response(): void
    {
        // 1. Arrange
        $messageType = 'Final Message';
        $llmResponseText = "  Here is the final message. \n"; // With extra whitespace
        $trimmedResponse = trim($llmResponseText);

        $fakeResponse = TextResponseFake::make()->withText($llmResponseText);
        Prism::fake([$fakeResponse]);

        /** @var MockInterface */
        $command = $this->command;

        /** @var Expectation $expectation */
        $expectation = $command->shouldReceive('option');
        $expectation->with('only-prompt')->andReturn(false);

        // Mute other console output methods.
        $command->shouldReceive('newLine', 'info', 'line');

        // This is the primary assertion for this test.
        /** @var Expectation $expectation */
        $expectation = $command->shouldReceive('output');
        $expectation->with($trimmedResponse, $messageType)->once();

        // 2. Act
        /** @var TestCommandForPrism $command */
        $command->invokeGenerateLlmResponse('some prompt', $messageType);

        // 3. Assert (Handled by Mockery's expectation)
    }
}
