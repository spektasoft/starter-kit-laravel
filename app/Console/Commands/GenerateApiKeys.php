<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
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
    public function handle(): void
    {
        $envPath = base_path('.env');
        $currentEnvContent = file_exists($envPath) ? @file_get_contents($envPath) : '';

        if ($currentEnvContent === false) {
            $this->error('Could not read .env file to check for existing API key. Proceeding without confirmation.');
            $currentEnvContent = '';
        }

        if (Str::contains($currentEnvContent, 'API_KEY=')) {
            if (! $this->confirm('An API key already exists. Do you want to overwrite it?')) {
                $this->info('API key generation cancelled.');

                return;
            }
        }

        $key = Str::random(32);

        $this->setKeyInEnvironmentFile($key);

        $this->info('API key generated successfully.');
    }

    /**
     * Set the API key in the environment file.
     *
     * @param  string  $key
     * @return void
     */
    protected function setKeyInEnvironmentFile($key)
    {
        $path = base_path('.env');

        if (file_exists($path)) {
            $currentContent = @file_get_contents($path);

            if ($currentContent === false) {
                $this->error('Could not read .env file.');

                return;
            }

            if (preg_match('/^API_KEY=/m', $currentContent)) {
                $currentContent = preg_replace(
                    '/^API_KEY=.*$/m',
                    'API_KEY='.$key,
                    $currentContent
                );
            } else {
                $currentContent .= PHP_EOL.'API_KEY='.$key;
            }
            file_put_contents($path, $currentContent);
        }
    }
}
