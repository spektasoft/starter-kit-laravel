<?php

namespace App\Console\Commands\Llm;

use App\Console\Commands\Llm\Concerns\HandlesLlmOutput;
use Illuminate\Console\Command;
use Prism\Prism\Prism;
use Symfony\Component\Process\Process;

class Commit extends Command
{
    use HandlesLlmOutput;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'llm:commit {--editor= : Specify the editor to use (e.g., nano, vim, code --wait)} {--only-prompt : Only generate and output the prompt without sending it to the agent}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates a conventional commit message based on staged Gitchanges using Prism';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        if (app()->environment('production')) {
            $this->error('This command can only be run in a development environment.');

            return;
        }

        $process = new Process(['git', 'diff', '--staged']);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error('Failed to get git diff: '.$process->getErrorOutput());

            return;
        }

        $gitDiff = $process->getOutput();

        if (empty($gitDiff)) {
            $this->error('No staged changes to commit');

            return;
        }

        $template = <<<'MARKDOWN'
        Generate a Conventional Commit `commit message` based on the following Git diff:

        ```
        :git_diff
        ```

        The `commit message` should be strictly in this format:

        <format>
        type: brief description

        body of the commit message
        </format>

        The `type` should be one of fix, feat, docs, style, refactor, perf, test, build, ci, chore, or revert. Please identify the `type` from the diff, create the `brief description`, and include a `body of the commit message` that describes the changes, reasoning, or any other relevant information. The `brief description` must start the sentence with a lowercase letter. Do not add explanations, only the `commit message`.
        MARKDOWN;

        $prompt = strtr($template, [
            ':git_diff' => $gitDiff,
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
        $this->info('Proposed commit message:');
        $this->info('------------------------');
        $this->line($proposedMessage);
        $this->info('------------------------');

        $this->info('Elapsed time: '.round($elapsedTime, 2).' seconds');
        $this->info("Prompt tokens: {$response->usage->promptTokens}");
        $this->info("Completion tokens: {$response->usage->completionTokens}");

        $this->openInEditor($proposedMessage, 'commit message');
    }
}
