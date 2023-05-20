<?php

namespace Back2Lobby\AccessControl\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $user_id
 * @property int $role_id
 * @property string $roleable_type
 * @property int $roleable_id
 */
class AssignedRole extends Pivot
{
    protected $table = 'assigned_roles';

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
