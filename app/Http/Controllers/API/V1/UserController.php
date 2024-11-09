<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\User\UserCollection;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
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

    public function show(User $user): JsonResponse
    {
        return response()->json(
            new UserResource($user),
            JsonResponse::HTTP_OK
        );
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

    public function update(User $user, Request $request): JsonResponse
    {
        $input = $request->all();

        Validator::make($input, [
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', Password::default()],
        ])->validate();

        if (config('fortify.lowercase_usernames')) {
            $request->merge([
                Fortify::username() => Str::lower($request->{Fortify::username()}),
            ]);
        }

        $dataToUpdate = [];

        if (isset($input['name'])) {
            $dataToUpdate['name'] = $input['name'];
        }

        if (isset($input['email'])) {
            $dataToUpdate['email'] = $input['email'];
        }

        if (isset($input['password'])) {
            $dataToUpdate['password'] = Hash::make($input['password']);
        }

        try {
            $user->update($dataToUpdate);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update user.'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json(
            ['message' => 'User updated successfully!'],
            JsonResponse::HTTP_ACCEPTED
        );
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
