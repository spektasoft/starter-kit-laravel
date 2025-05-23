<?php

namespace App\Console\Commands\Llm;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Prism\Prism\Prism;
use Symfony\Component\Process\Process;

class Pr extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'llm:pr {--editor= : Specify the editor to use (e.g., nano, vim, code --wait)}';

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

        ```
        branch name

        type: brief description

        body of the pull request message
        ```

        The `branch name` must be in kebab-case derived from `brief description`. The `type` should be one of fix, feat, docs, style, refactor, perf, test, build, ci, chore, or revert. Please identify the `type` from the commit messages, create the `brief description`, and include a `body of the pull request message` that describes the changes, reasoning, or any other relevant information. The `brief description` must start the sentence with a lowercase letter. The `body of the pull request message` should be wrapped in paragraphs using Markdown format. Do not add explanations, only the `pull request message`.
        MARKDOWN;

        $prompt = strtr($template, [
            ':commit_messages' => $commitMessages,
        ]);

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

        /** @var ?string */
        $editor = $this->option('editor');

        if (! $editor) {
            $editInEditor = $this->confirm('Do you want to open this message in an editor to make changes?');
        } else {
            $editInEditor = true;
        }

        if ($editInEditor) {
            if (! $editor) {
                // If no editor is found, ask the user
                /** @var ?string */
                $editor = $this->ask('Please enter the command for your preferred editor (e.g., nano, vim, code --wait):');
            }

            if ($editor) {
                // Create a unique temporary file path for the PR message
                $tempFilePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'llm_pr_'.Str::uuid()->toString().'.md';

                // Write the proposed message to the temporary file
                File::put($tempFilePath, $proposedMessage);

                $this->info("Opening pull request message in editor: '{$editor} {$tempFilePath}'");

                // Construct the process command string, escaping the file path
                $command = "{$editor} ".escapeshellarg($tempFilePath);

                $process = Process::fromShellCommandline($command);
                $process->setTimeout(null); // Allow indefinite editing time
                $process->setTty(Process::isTtySupported()); // Enable TTY for interactive editors

                try {
                    $process->run(); // Execute the editor process
                } catch (\Exception $e) {
                    $this->error('Error during editor execution: '.$e->getMessage());
                } finally {
                    // Clean up the temporary file
                    File::delete($tempFilePath);
                }
            } else {
                $this->warn('No editor command provided. Skipping editor step.');
            }
        }
    }
}
