<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;

class ArtisanController
{
    public function keyGenerate(): JsonResponse
    {
        $result = Artisan::call('key:generate');

        return response()->json([
            'result' => $result,
        ]);
    }

    public function migrate(): JsonResponse
    {
        $result = Artisan::call('migrate --force');

        return response()->json([
            'result' => $result,
        ]);
    }

    public function optimize(): JsonResponse
    {
        $result = Artisan::call('optimize');

        return response()->json([
            'result' => $result,
        ]);
    }

    public function storageLink(): JsonResponse
    {
        $result = Artisan::call('storage:link');

        return response()->json([
            'result' => $result,
        ]);
    }
}
