<?php

namespace App\Console\Commands\Llm;

use App\Console\Commands\Llm\Concerns\HandlesLlmOutput;
use Illuminate\Console\Command;
use Prism\Prism\Prism;
use Symfony\Component\Process\Process;

class Pr extends Command
{
    use HandlesLlmOutput;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'llm:pr {--editor= : Specify the editor to use (e.g., nano, vim, code --wait)} {--only-prompt : Only generate and output the prompt without sending it to the agent}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates a conventional pull request message based on
commit range using Prism';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        if (app()->environment('production')) {
            $this->error('This command can only be run in a development environment.');

            return;
        }

        /** @var string */
        $commit_hash_1 = $this->ask('Please enter first commit hash (older)');
        /** @var string */
        $commit_hash_2 = $this->ask('Please enter second commit hash (newer)');

        $process = new Process(['git', 'log', '--format=%B%n---%n', '--reverse', "$commit_hash_1..$commit_hash_2"]);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error('Failed to get commit messages: '.$process->getErrorOutput());

            return;
        }

        $commitMessages = $process->getOutput();

        if (empty($commitMessages)) {
            $this->error('No commit messages');

            return;
        }

        $template = <<<'MARKDOWN'
        Generate a Conventional Commit `pull request message` based on the following commit messages:

        ```
        :commit_messages
        ```

        The `pull request message` should be strictly in this format:

        <format>
        branch name

        type: brief description

        body of the pull request message
        </format>

        The `branch name` must be in kebab-case derived from `brief description`. The `type` should be one of fix, feat, docs, style, refactor, perf, test, build, ci, chore, or revert. Please identify the `type` from the commit messages, create the `brief description`, and include a `body of the pull request message` that describes the changes, reasoning, or any other relevant information. The `brief description` must start the sentence with a lowercase letter. Do not add explanations, only the `pull request message`.
        MARKDOWN;

        $prompt = strtr($template, [
            ':commit_messages' => $commitMessages,
        ]);

        if ($this->option('only-prompt')) {
            $this->info('Generated Prompt:');
            $this->line($prompt);

            $this->openInEditor($prompt, 'prompt');

            return;
        }

        /** @var string */
        $usingProvider = config('prism.using.provider');
        /** @var string */
        $usingModel = config('prism.using.model');

        $startTime = microtime(true);

        $response = Prism::text()
            ->using($usingProvider, $usingModel)
            ->withPrompt($prompt)
            ->asText();

        $endTime = microtime(true);
        $elapsedTime = $endTime - $startTime;

        $proposedMessage = trim($response->text);

        $this->newLine();
        $this->info('Proposed pull request:');
        $this->info('------------------------');
        $this->line($proposedMessage);
        $this->info('------------------------');
        $this->info('Elapsed time: '.round($elapsedTime, 2).' seconds');
        $this->info("Prompt tokens: {$response->usage->promptTokens}");
        $this->info("Completion tokens: {$response->usage->completionTokens}");

        $this->openInEditor($proposedMessage, 'pull request message');
    }
}
