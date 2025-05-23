<?php

namespace App\Console\Commands\Llm;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Prism\Prism\Prism;
use Symfony\Component\Process\Process;

class Commit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'llm:commit {--editor= : Specify the editor to use (e.g., nano, vim, code --wait)}';

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
                // Create a unique temporary file path for the commit message
                $tempFilePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'llm_commit_'.Str::uuid()->toString().'.md';

                // Write the proposed message to the temporary file
                File::put($tempFilePath, $proposedMessage);

                $this->info("Opening commit message in editor: '{$editor} {$tempFilePath}'");

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
