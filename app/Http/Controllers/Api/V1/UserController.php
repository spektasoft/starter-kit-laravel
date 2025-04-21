<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\AuthorizesRequestsWithTokens;
use App\Data\UserData;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
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
    use AuthorizesRequestsWithTokens;

    /**
     * @return AbstractPaginator<int, UserData>|Enumerable<int, UserData>
     */
    public function index()
    {
        $this->authorizeTokenOrPolicy('read', 'viewAny', User::class);

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
        $this->authorizeTokenOrPolicy('read', 'view', $user);

        return UserData::from($user);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeTokenOrPolicy('create', 'create', User::class);

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
        $this->authorizeTokenOrPolicy('update', 'update', $user);

        $request = $this->normalizeRequest($request);

        $input = $request->all();

        Validator::make($input, [
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', Password::default()],
        ])->validate();

        $dataToUpdate = [];

        if (array_key_exists('name', $input)) {
            $dataToUpdate['name'] = $input['name'];
        }

        if (array_key_exists('email', $input)) {
            $dataToUpdate['email'] = $input['email'];
        }

        if (array_key_exists('password', $input) && ! is_null($input['password'])) {
            /** @var string */
            $password = $input['password'];
            $dataToUpdate['password'] = Hash::make($password);
        }
        try {
            $user->update($dataToUpdate);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update user.'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json(['message' => 'User updated successfully!'], JsonResponse::HTTP_ACCEPTED);
    }

    public function destroy(User $user): JsonResponse
    {
        $this->authorizeTokenOrPolicy('delete', 'delete', $user);

        $user->delete();

        return response()->json(
            ['message' => 'User deleted successfully!'],
            JsonResponse::HTTP_OK
        );
    }

    public function can(Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required',
            'resource' => 'required',
            'id' => 'nullable',
        ]);

        /** @var string */
        $action = $request->input('action');
        /** @var string */
        $resource = $request->input('resource');
        /** @var string|null */
        $id = $request->input('id');

        /** @var class-string<Model> */
        $modelClass = $this->guessModelFromResource($resource);
        /** @var Model|class-string<Model> */
        $model = $modelClass;
        if ($id) {
            /** @var ?Model */
            $modelInstance = $modelClass::find($id);
            if (! $modelInstance) {
                throw (new ModelNotFoundException)->setModel($modelClass, $id);
            }
            $model = $modelInstance;
        }

        if (! request()->user()?->can($action, $model)) {
            return response()->json(
                ['message' => 'Permission denied!'],
                JsonResponse::HTTP_FORBIDDEN
            );
        }

        return response()->json(
            ['message' => 'Permission granted!'],
            JsonResponse::HTTP_OK
        );
    }

    /**
     * @return class-string<Model>
     */
    private function guessModelFromResource(string $resource)
    {
        $namespace = 'App\\Models\\';
        /** @var class-string<Model> */
        $modelName = $namespace.ucfirst($resource);

        if (class_exists($modelName)) {
            return $modelName;
        }

        /** @var class-string<Model> */
        $modelName = $namespace.ucfirst(str($resource)->singular());

        if (class_exists($modelName)) {
            return $modelName;
        }

        throw new ModelNotFoundException;
    }

    private function normalizeRequest(Request $request): Request
    {
        if (config('fortify.lowercase_usernames')) {
            $usernameKey = Fortify::username();
            if ($request->has($usernameKey)) {
                /** @var string|null */
                $username = $request->input($usernameKey);
                $request->merge([
                    $usernameKey => filled($username) ? Str::lower($username) : $username,
                ]);
            }
        }

        return $request;
    }
}
