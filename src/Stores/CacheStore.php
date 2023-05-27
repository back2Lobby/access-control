<?php

namespace Back2Lobby\AccessControl\Stores;

use Back2Lobby\AccessControl\Models\AssignedPermission;
use Back2Lobby\AccessControl\Models\Permission;
use Back2Lobby\AccessControl\Models\Role;
use Back2Lobby\AccessControl\Stores\Abstracts\CacheStoreBase;
use Back2Lobby\AccessControl\Stores\Enumerations\SyncFlag;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CacheStore extends CacheStoreBase
{
    private Collection $roles;

    private Collection $permissions;

    private Collection $maps;

    public function sync(SyncFlag $flag = SyncFlag::Everything): void
    {
        if ($flag === SyncFlag::Everything || $flag === SyncFlag::OnlyRoles) {
            $this->roles = Role::get();
        }

        if ($flag === SyncFlag::Everything || $flag === SyncFlag::OnlyPermissions) {
            $this->permissions = Permission::get();
        }

        if ($flag === SyncFlag::Everything || $flag === SyncFlag::OnlyMaps) {
            $this->maps = AssignedPermission::get();
        }

        // cache updated object
        $this->cache();
    }

    public function cache(): void
    {
        // cache store data for 1 day
        Cache::driver(static::$cacheDriver)->put('available_roles', $this->roles, (60 * 60) * 24);
        Cache::driver(static::$cacheDriver)->put('available_permissions', $this->permissions, (60 * 60) * 24);
        Cache::driver(static::$cacheDriver)->put('available_maps', $this->maps, (60 * 60) * 24);
    }

    public function clearCache(): void
    {
        Cache::driver(static::$cacheDriver)->forget('available_roles');
        Cache::driver(static::$cacheDriver)->forget('available_permissions');
        Cache::driver(static::$cacheDriver)->forget('available_maps');
    }

    public function reset(): void
    {
        $this->roles = collect();
        $this->permissions = collect();
        $this->maps = collect();
    }

    public static function hasCached(): bool
    {
        return Cache::driver(static::$cacheDriver)->has('available_roles') &&
            Cache::driver(static::$cacheDriver)->has('available_permissions') &&
            Cache::driver(static::$cacheDriver)->has('available_maps');
    }

    public function loadFromCache(): void
    {
        $this->roles = Cache::driver(static::$cacheDriver)->get('available_roles');
        $this->permissions = Cache::driver(static::$cacheDriver)->get('available_permissions');
        $this->maps = Cache::driver(static::$cacheDriver)->get('available_maps');
    }

    public function getRoles(): Collection
    {
        return $this->roles;
    }

    public function getPermissions(): Collection
    {
        return $this->permissions;
    }

    public function getMaps(): Collection
    {
        return $this->maps;
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
        return $this->maps->where('role_id', $role->id)->where('forbidden', false)->map(function ($permissionRole) {
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
        return $this->maps->where('role_id', $role->id)->where('forbidden', true)->map(function ($permissionRole) {
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
        return $this->maps->where('permission_id', $permission->id)->where('forbidden', false)->map(function ($permissionRole) {
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
        return $this->maps->where('permission_id', $permission->id)->where('forbidden', true)->map(function ($permissionRole) {
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
