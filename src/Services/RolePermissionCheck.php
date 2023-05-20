<?php

namespace Back2Lobby\AccessControl\Services;

use Back2Lobby\AccessControl\Models\Permission;
use Back2Lobby\AccessControl\Models\Role;
use Back2Lobby\AccessControl\Stores\Abstracts\CacheStoreBase;

class RolePermissionCheck
{
    public function __construct(private readonly CacheStoreBase $store, private readonly Role $role)
    {
    }

    /**
     * Check if role have a specific permission either directly or indirectly
     */
    public function do(Permission|string $permission): bool
    {
        if ($permission = $this->store->getPermission($permission)) {
            return $this->store->canRoleDo($this->role, $permission);
        } else {
            return false;
        }
    }
}
