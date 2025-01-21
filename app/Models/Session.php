<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property ?string $user_id
 * @property ?string $ip_address
 * @property ?string $user_agent
 * @property ?string $payload
 * @property int $last_activity
 */
class Session extends Model
{
    use HasUuids;
}
