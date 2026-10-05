<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class GenerateApiKeys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api-key:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the API key in the .env file.';

    /**
     * Execute the console command.
     */
    public function handle(Filesystem $files): int
    {
        $envPath = app()->environmentFilePath();

        try {
            /** @var string|false $currentEnvContent */
            $currentEnvContent = $files->exists($envPath)
                ? @$files->get($envPath)
                : '';
        } catch (Exception) {
            $this->error('Could not read environment file.');

            return self::FAILURE;
        }

        if ($currentEnvContent === false) {
            $this->error('Could not read environment file.');

            return self::FAILURE;
        }

        if (preg_match('/^API_KEY=/m', $currentEnvContent)) {
            if (! $this->confirm('An API key already exists. Do you want to overwrite it?')) {
                $this->info('API key generation cancelled.');

                return self::SUCCESS;
            }
        }

        $key = Str::random(32);

        if (! $this->setKeyInEnvironmentFile($files, $envPath, $currentEnvContent, $key)) {
            $this->error('Could not write environment file.');

            return self::FAILURE;
        }

        $this->info('API key generated successfully.');

        return self::SUCCESS;
    }

    /**
     * Set the API key using the previously read environment content.
     */
    protected function setKeyInEnvironmentFile(
        Filesystem $files,
        string $path,
        string $currentContent,
        string $key
    ): bool {
        if (preg_match('/^API_KEY=/m', $currentContent)) {
            $currentContent = preg_replace(
                '/^API_KEY=.*$/m',
                'API_KEY='.$key,
                $currentContent
            );
        } else {
            $currentContent .= PHP_EOL.'API_KEY='.$key;
        }

        if ($currentContent === null) {
            return false;
        }

        try {
            $bytesWritten = @$files->put($path, $currentContent);
        } catch (Exception) {
            return false;
        }

        return $bytesWritten === strlen($currentContent);
    }
}
