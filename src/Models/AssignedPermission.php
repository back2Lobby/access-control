<?php

namespace Back2Lobby\AccessControl\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $permission_id
 * @property int $role_id
 * @property bool $forbidden
 */
class AssignedPermission extends Pivot
{
    use HasFactory;

    protected $table = 'assigned_permissions';

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }
}
