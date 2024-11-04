<?php

namespace App\Http\Resources\User;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UserCollection extends ResourceCollection
{
    /**
     * @return Arrayable<string, mixed>
     */
    public function toArray(Request $request)
    {
        /** @var Arrayable<string, mixed> */
        $arr = parent::toArray($request);

        return $arr;
    }
}
