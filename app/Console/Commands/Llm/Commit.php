<?php

namespace App\Console\Commands\Llm;

use App\Console\Commands\Llm\Concerns\HandlesLlmOutput;
use App\Console\Commands\Llm\Concerns\InteractsWithPrism;
use App\Console\Commands\Llm\Concerns\RunsInDevelopment;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class Commit extends Command
{
    use HandlesLlmOutput;
    use InteractsWithPrism;
    use RunsInDevelopment;

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
    public function handle(): int
    {
        if (! $this->ensureDevelopmentEnvironment()) {
            return self::FAILURE;
        }

        $process = resolve(Process::class, ['command' => ['git', 'diff', '--staged']]);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error('Failed to get git diff: '.$process->getErrorOutput());

            return self::FAILURE;
        }

        $gitDiff = $process->getOutput();

        if (empty($gitDiff)) {
            $this->error('No staged changes to commit');

            return self::FAILURE;
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

        $this->generateLlmResponse($prompt, 'commit message');

        return self::SUCCESS;
    }
}
