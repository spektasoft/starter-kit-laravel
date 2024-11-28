<?php

namespace App\Http\Controllers\Api\V1;

use App\Data\UserData;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Utils\Authorizer;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Fortify;

class UserController extends Controller
{
    /**
     * @return AbstractPaginator<UserData>|Enumerable<string|int, UserData>
     */
    public function index()
    {
        Authorizer::authorizeToken('read');
        Authorizer::authorize('view_any_user', User::class);

        /** @var string */
        $tableSortColumn = request()->input('tableSortColumn') ?? 'id';
        /** @var string */
        $tableSortDirection = request()->input('tableSortDirection') ?? 'asc';

        $users = User::orderBy($tableSortColumn, $tableSortDirection)
            ->paginate();

        return UserData::collect($users);
    }

    public function me(): UserData
    {
        return UserData::from(User::auth());
    }

    public function show(User $user): UserData
    {
        Authorizer::authorizeToken('read');
        Authorizer::authorize('view_user', $user);

        return UserData::from($user);
    }

    public function store(Request $request): JsonResponse
    {
        Authorizer::authorizeToken('create');
        Authorizer::authorize('create_user', User::class);

        $request = $this->normalizeRequest($request);

        $input = $request->all();

        Validator::make($input, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ])->validate();

        /** @var string */
        $password = $input['password'];

        User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($password),
        ]);

        return response()->json(
            ['message' => 'User created successfully!'],
            JsonResponse::HTTP_CREATED
        );
    }

    public function update(User $user, Request $request): JsonResponse
    {
        Authorizer::authorizeToken('update');
        Authorizer::authorize('update_user', $user);

        $input = $request->all();

        Validator::make($input, [
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', Password::default()],
        ])->validate();

        $request = $this->normalizeRequest($request);

        $dataToUpdate = [];

        if (isset($input['name'])) {
            $dataToUpdate['name'] = $input['name'];
        }

        if (isset($input['email'])) {
            $dataToUpdate['email'] = $input['email'];
        }

        if (isset($input['password'])) {
            /** @var string */
            $password = $input['password'];
            $dataToUpdate['password'] = Hash::make($password);
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
        Authorizer::authorizeToken('delete');
        Authorizer::authorize('delete_user', $user);

        $user->delete();

        return response()->json(
            ['message' => 'User deleted successfully!'],
            JsonResponse::HTTP_OK
        );
    }

    public function can(Request $request): JsonResponse
    {
        $request->validate([
            'permission' => 'required',
            'resource' => 'required',
            'id' => 'nullable',
        ]);

        /** @var string */
        $permission = $request->input('permission');
        /** @var string */
        $resource = $request->input('resource');
        /** @var string|null */
        $id = $request->input('id');

        $model = $this->guessModelFromResource($resource);
        if ($id) {
            $model = $model::find($id);
        }

        if (User::auth()?->cannot($permission, $model)) {
            return response()->json(
                ['message' => 'Permission denies!'],
                JsonResponse::HTTP_FORBIDDEN
            );
        }

        return response()->json(
            ['message' => 'Permission granted!'],
            JsonResponse::HTTP_OK
        );
    }

    /**
     * @return class-string
     */
    private function guessModelFromResource(string $resource)
    {
        if (substr($resource, -1) === 's') {
            $resource = substr($resource, 0, -1);
        }

        $modelName = 'App\\Models\\'.ucfirst($resource);

        if (class_exists($modelName)) {
            return $modelName;
        }

        throw new ModelNotFoundException;
    }

    private function normalizeRequest(Request $request): Request
    {
        if (config('fortify.lowercase_usernames')) {
            /** @var string */
            $username = $request->{Fortify::username()};
            $request->merge([
                Fortify::username() => Str::lower($username),
            ]);
        }

        return $request;
    }
}
