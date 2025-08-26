<?php

namespace App\Console\Commands\Llm\Concerns;

use Illuminate\Console\Command;
use Prism\Prism\Prism;

/**
 * @mixin Command
 */
trait InteractsWithPrism
{
    use HandlesLlmOutput;

    protected function generateLlmResponse(string $prompt, string $messageType): void
    {
        if ($this->option('only-prompt')) {
            $this->info('Generated Prompt:');
            $this->line($prompt);

            $this->output($prompt, 'prompt');

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
        $this->info("Proposed {$messageType}:");
        $this->info('------------------------');
        $this->line($proposedMessage);
        $this->info('------------------------');

        $this->info('Elapsed time: '.round($elapsedTime, 2).' seconds');
        $this->info("Prompt tokens: {$response->usage->promptTokens}");
        $this->info("Completion tokens: {$response->usage->completionTokens}");

        $this->output($proposedMessage, $messageType);
    }
}
