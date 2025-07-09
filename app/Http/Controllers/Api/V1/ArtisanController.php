<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ArtisanController
{
    public function keyGenerate(): JsonResponse
    {
        return $this->callArtisanCommand('key:generate');
    }

    public function migrate(): JsonResponse
    {
        return $this->callArtisanCommand('migrate --force');
    }

    public function optimize(): JsonResponse
    {
        return $this->callArtisanCommand('optimize');
    }

    public function seedPermissions(): JsonResponse
    {
        return $this->callArtisanCommand('seed:permissions');
    }

    public function storageLink(): JsonResponse
    {
        return $this->callArtisanCommand('storage:link');
    }

    private function callArtisanCommand(string $command): JsonResponse
    {
        Log::warning("Artisan command '{$command}' initiated via API.");

        try {
            Artisan::call($command);
            $output = Artisan::output();

            Log::info("Artisan command '{$command}' executed successfully via API. Output: {$output}");
            // For sensitive commands, log at a higher level for alerting
            if (in_array($command, ['key:generate', 'migrate --force', 'api-key:generate'])) {
                Log::critical("CRITICAL: Sensitive Artisan command '{$command}' executed successfully via API.");
            }

            return response()->json([
                'status' => 'success',
                'command' => $command,
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            Log::error("Artisan command '{$command}' failed via API. Error: {$e->getMessage()}. Output: " . Artisan::output());
            Log::critical("CRITICAL: Artisan command '{$command}' failed via API. Error: {$e->getMessage()}.");

            return response()->json([
                'status' => 'error',
                'command' => $command,
                'message' => $e->getMessage(),
                'output' => Artisan::output(),
            ], 500);
        }
    }
}
