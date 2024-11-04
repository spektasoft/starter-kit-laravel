<?php

namespace App\Http\Controllers;

use App\Http\Resources\User\UserCollection;
use App\Models\User;

class UserController extends Controller
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
}
