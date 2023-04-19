<?php

namespace Back2Lobby\AccessControl\Models;

use Back2Lobby\AccessControl\Traits\syncOnEvents;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $permission_id
 * @property int $role_id
 * @property bool $forbidden
 */
class PermissionRole extends Pivot
{
    use HasFactory, syncOnEvents;

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }
}
