<?php

namespace App\Console\Commands\Llm;

use App\Console\Commands\Llm\Concerns\InteractsWithPrism;
use App\Console\Commands\Llm\Concerns\RunsInDevelopment;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class Pr extends Command
{
    use InteractsWithPrism;
    use RunsInDevelopment;

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
    protected $description = 'Generates a conventional pull request message based on commit range using Prism';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! $this->ensureDevelopmentEnvironment()) {
            return self::FAILURE;
        }

        /** @var string */
        $commit_hash_1 = $this->ask('Please enter first commit hash (older)');
        /** @var string */
        $commit_hash_2 = $this->ask('Please enter second commit hash (newer)');

        $process = resolve(Process::class, ['command' => ['git', 'log', '--format=%B%n---%n', '--reverse', "$commit_hash_1..$commit_hash_2"]]);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error('Failed to get commit messages: '.$process->getErrorOutput());

            return self::FAILURE;
        }

        $commitMessages = $process->getOutput();

        if (empty($commitMessages)) {
            $this->error('No commit messages');

            return self::FAILURE;
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

        $this->generateLlmResponse($prompt, 'pull request message');

        return self::SUCCESS;
    }
}
