<?php

namespace App\Console\Commands\Llm\Concerns;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

/**
 * @mixin Command
 */
trait HandlesLlmOutput
{
    /**
     * Opens content in a user-specified editor.
     */
    protected function openInEditor(string $content, string $type): void
    {
        /** @var ?string */
        $editor = $this->option('editor');

        if (! $editor) {
            $editInEditor = $this->confirm("Do you want to open this {$type} in an editor to make changes?");
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
                // Create a unique temporary file path
                $tempFilePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'llm_'.Str::slug($type).'_'.Str::uuid()->toString().'.md';

                // Write the content to the temporary file
                File::put($tempFilePath, $content);

                $this->info("Opening {$type} in editor: '{$editor} {$tempFilePath}'");

                // Construct the process command string, escaping the file path
                $command = "{$editor} ".escapeshellarg($tempFilePath);

                $process = $this->makeProcess($command);
                $process->setTimeout(null); // Allow indefinite editing time
                $process->setTty(Process::isTtySupported()); // Enable TTY for interactive editors

                try {
                    $process->run(); // Execute the editor process

                    // Add error handling for process execution
                    if (! $process->isSuccessful()) {
                        $this->error('Editor process failed: '.$process->getErrorOutput());
                    }
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

    /**
     * Creates a new Process instance.
     * This method is isolated to allow for easy mocking in tests.
     */
    protected function makeProcess(string $command): Process
    {
        return Process::fromShellCommandline($command);
    }
}
