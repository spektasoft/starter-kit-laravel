<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\User\UserCollection;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

class UserController
{
    public function index(): UserCollection
    {
        if (request()->expectsJson()) {
            /** @var string */
            $tableSortColumn = request()->input('tableSortColumn') ?? 'id';
            /** @var string */
            $tableSortDirection = request()->input('tableSortDirection') ?? 'asc';

            $users = User::orderBy($tableSortColumn, $tableSortDirection)
                ->paginate();

            return new UserCollection($users);
        } else {
            abort(403);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            if (config('fortify.lowercase_usernames')) {
                $request->merge([
                    Fortify::username() => Str::lower($request->{Fortify::username()}),
                ]);
            }

            $input = $request->all();

            Validator::make($input, [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
            ])->validate();

            User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => Hash::make($input['password']),
            ]);

            return response()->json(
                ['message' => 'User created successfully!'],
                JsonResponse::HTTP_CREATED
            );
        } catch (ValidationException $e) {
            return response()->json([
                'errors' => $e->errors(),
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            return response()->json([
                'errors' => ['An unexpected error occurred.'],
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(User $user): JsonResponse
    {
        if (! request()->expectsJson()) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $user->delete();

        return response()->json(
            ['message' => 'User deleted successfully!'],
            JsonResponse::HTTP_OK
        );
    }
}
