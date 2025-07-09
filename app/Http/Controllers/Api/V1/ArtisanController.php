<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;

class ArtisanController
{
    public function apiKeyGenerate(): JsonResponse
    {
        return $this->callArtisanCommand('api-key:generate');
    }

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
        try {
            Artisan::call($command);
            $output = Artisan::output();

            return response()->json([
                'status' => 'success',
                'command' => $command,
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'command' => $command,
                'message' => $e->getMessage(),
                'output' => Artisan::output(),
            ], 500);
        }
    }
}
