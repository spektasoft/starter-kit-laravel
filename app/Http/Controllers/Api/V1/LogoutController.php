<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        try {
            /** @var User */
            $user = Auth::user();
            /** @var PersonalAccessToken|mixed|null */
            $token = $user->currentAccessToken();
            if ($token && $token instanceof PersonalAccessToken) {
                $token->delete();
            }

            return response()->json([
                'message' => 'Logged out successfully.',
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'errors' => ['User or token not found.'],
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'errors' => ['An unexpected error occurred: '.$e->getMessage()],
            ], 500);
        }
    }
}
