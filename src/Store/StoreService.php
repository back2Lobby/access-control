<?php

namespace Back2Lobby\AccessControl\Store;

use Back2Lobby\AccessControl\Models\Permission;
use Back2Lobby\AccessControl\Models\PermissionRole;
use Back2Lobby\AccessControl\Models\Role;
use Back2Lobby\AccessControl\Store\Abstracts\Storable;
use Back2Lobby\AccessControl\Store\Enumerations\SyncFlag;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class StoreService extends Storable
{
    private Collection $roles;

    private Collection $permissions;

    private Collection $map;

    public function sync(SyncFlag $flag = SyncFlag::SyncAll): void
    {
        if ($flag === SyncFlag::SyncAll || $flag === SyncFlag::OnlyRole) {
            $this->roles = Role::get();
        }

        if ($flag === SyncFlag::SyncAll || $flag === SyncFlag::OnlyPermission) {
            $this->permissions = Permission::get();
        }

        if ($flag === SyncFlag::SyncAll || $flag === SyncFlag::OnlyMap) {
            $this->map = PermissionRole::get();
        }

        // cache updated object
        $this->cache();
    }

    public function cache(): void
    {
        // cache store for 1 day
        Cache::put('access-store', $this, (60 * 60) * 24);
    }

    public function clearCache(): void
    {
        Cache::forget('access-store');
    }

    public function reset(): void
    {
        $this->roles = collect();
        $this->permissions = collect();
        $this->map = collect();
    }

    public function getRoles(): Collection
    {
        return $this->roles;
    }

    public function getPermissions(): Collection
    {
        return $this->permissions;
    }

    public function getMap(): Collection
    {
        return $this->map;
    }

    public function getRole(Role|string|int $role): Role|null
    {
        $key = is_string($role) ? 'name' : 'id';
        $value = $role instanceof Role ? $role->id : $role;

        return $this->roles->where($key, $value)->first();
    }

    public function getPermission(Permission|string|int $permission): Permission|null
    {
        $key = is_string($permission) ? 'name' : 'id';
        $value = $permission instanceof Permission ? $permission->id : $permission;

        return $this->permissions->where($key, $value)->first();
    }

    public function getAllPermissionsOf(Role $role): Collection
    {
        return $this->getAllowedPermissionsOf($role)->concat($this->getForbiddenPermissionsOf($role))->unique('id');
    }

    public function getAllowedPermissionsOf(Role $role): Collection
    {
        return $this->getDirectlyAllowedPermissionsOf($role)->concat($this->getIndirectlyAllowedPermissionsOf($role))->unique('id');
    }

    public function getDirectlyAllowedPermissionsOf(Role $role): Collection
    {
        return $this->map->where('role_id', $role->id)->where('forbidden', false)->map(function ($permissionRole) {
            return $this->getPermission($permissionRole->permission_id);
        });
    }

    public function getIndirectlyAllowedPermissionsOf(Role $role): Collection
    {
        // check if the role have any super permissions
        $superPermissions = $this->getDirectlyAllowedPermissionsOf($role)->where('name', '*');

        //return all the available permission which are not forbidden for this role and also remove super permissions
        return $superPermissions->count() > 0 ?
            $this->permissions->diff($this->getForbiddenPermissionsOf($role))->diff($superPermissions) :
            $superPermissions;
    }

    public function getForbiddenPermissionsOf(Role $role): Collection
    {
        return $this->getDirectlyForbiddenPermissionsOf($role)->concat($this->getIndirectlyForbiddenPermissionsOf($role))->unique('id');
    }

    public function getDirectlyForbiddenPermissionsOf(Role $role): Collection
    {
        return $this->map->where('role_id', $role->id)->where('forbidden', true)->map(function ($permissionRole) {
            return $this->getPermission($permissionRole->permission_id);
        });
    }

    public function getIndirectlyForbiddenPermissionsOf(Role $role): Collection
    {
        $forbiddenSuperPermissions = $this->getDirectlyForbiddenPermissionsOf($role)->where('name', '*');

        return $forbiddenSuperPermissions->count() > 0 ?
            $this->permissions->diff($this->getDirectlyAllowedPermissionsOf($role))->diff($forbiddenSuperPermissions) :
            $forbiddenSuperPermissions;
    }

    public function getAllRolesOf(Permission $permission): Collection
    {
        return $this->getAllowedRolesOf($permission)->concat($this->getForbiddenRolesOf($permission))->unique('id');
    }

    public function getAllowedRolesOf(Permission $permission): Collection
    {
        return $this->getDirectlyAllowedRolesOf($permission)->concat($this->getIndirectlyAllowedRolesOf($permission))->unique('id');
    }

    public function getDirectlyAllowedRolesOf(Permission $permission): Collection
    {
        return $this->map->where('permission_id', $permission->id)->where('forbidden', false)->map(function ($permissionRole) {
            return $this->getRole($permissionRole->role_id);
        });
    }

    public function getIndirectlyAllowedRolesOf(Permission $permission): Collection
    {
        // get all the roles that have super permission
        $roles = $this->getPermission('*') ? $this->getDirectlyAllowedRolesOf($this->getPermission('*')) : collect();

        // now only get roles that don't have any roleable specified, so we know that they are supposed to be
        // there for all roleables
        return $roles->filter(function ($r) use ($permission) {
            // also make sure given permission is not forbidden for this role
            return is_null($r->roleables) && $this->getDirectlyForbiddenPermissionsOf($r)->where('id', $permission->id)->count() === 0;
        });
    }

    public function getForbiddenRolesOf(Permission $permission): Collection
    {
        return $this->getDirectlyForbiddenRolesOf($permission)->concat($this->getIndirectlyForbiddenRolesOf($permission))->unique('id');
    }

    public function getDirectlyForbiddenRolesOf(Permission $permission): Collection
    {
        return $this->map->where('permission_id', $permission->id)->where('forbidden', true)->map(function ($permissionRole) {
            return $this->getRole($permissionRole->role_id);
        });
    }

    public function getIndirectlyForbiddenRolesOf(Permission $permission): Collection
    {
        // get all the roles that have super permission
        $roles = $this->getPermission('*') ? $this->getDirectlyForbiddenRolesOf($this->getPermission('*')) : collect();

        // now only get roles that don't have any roleable specified, so we know that they are supposed to be
        // there for all roleables
        return $roles->filter(function ($r) use ($permission) {
            // also make sure given permission is forbidden for this role
            return is_null($r->roleables) && $this->getDirectlyAllowedPermissionsOf($r)->where('id', $permission->id)->count() === 0;
        });
    }

    public function canRoleDo(Role $role, Permission $permission): bool
    {
        $forbiddenPermissions = $this->getForbiddenPermissionsOf($role);

        // check if this permission is forbidden
        if ($forbiddenPermissions->contains('id', $permission->id)) {
            return false;
        }

        // if user have super permission then no need to check anymore
        $allowedPermissions = $this->getAllowedPermissionsOf($role);

        if ($allowedPermissions->contains('name', '*')) {
            return true;
        }

        // otherwise check if it's there in allowed permissions
        return $allowedPermissions->contains('id', $permission->id);
    }
}
