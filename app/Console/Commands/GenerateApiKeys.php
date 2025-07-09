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

            if (Str::contains($currentContent, 'API_KEY=')) {
                file_put_contents($path, preg_replace(
                    '/^API_KEY=.*$/m',
                    'API_KEY='.$key,
                    $currentContent
                ));
            } else {
                file_put_contents($path, $currentContent . PHP_EOL . 'API_KEY=' . $key);
            }
        }
    }
}
