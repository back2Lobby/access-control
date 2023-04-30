<?php

namespace Back2Lobby\AccessControl\Store\Abstracts;

use Back2Lobby\AccessControl\Models\Permission;
use Back2Lobby\AccessControl\Models\Role;
use Back2Lobby\AccessControl\Store\Enumerations\SyncFlag;
use Back2Lobby\AccessControl\Store\StoreService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

abstract class Storable
{
    private static StoreService $store;

    private function __construct()
    {
    }

    public static function getInstance(): StoreService
    {
        return self::$store ??= Cache::get('access-store') ?? tap(new static, fn ($store) => $store->sync());
    }

    private function __clone()
    {
    }

    /**
     * Sync all roles and permissions with database
     *
     * @param  SyncFlag  $flag specify what should be synced
     */
    abstract public function sync(SyncFlag $flag = SyncFlag::SyncAll): void;

    /**
     * Cache this object
     */
    abstract public function cache(): void;

    /**
     * Clear the cached object
     */
    abstract public function clearCache(): void;

    /**
     * Reset this object removing all the roles, permissions and maps
     */
    abstract public function reset(): void;

    /**
     * Get all the roles available
     */
    abstract public function getRoles(): Collection;

    /**
     * Get all the permissions available
     */
    abstract public function getPermissions(): Collection;

    /**
     * Get all the maps available
     */
    abstract public function getMap(): Collection;

    /**
     * Get role if it exists
     */
    abstract public function getRole(Role|string|int $role): Role|null;

    /**
     * Get permission if it exists
     */
    abstract public function getPermission(Permission|string|int $permission): Permission|null;

    /**
     * Get all permissions (including allowed,forbidden and indirect permissions) of specified role
     */
    abstract public function getAllPermissionsOf(Role $role): Collection;

    /**
     * Get only allowed permissions (including both directly and indirectly allowed permissions) of specified role
     */
    abstract public function getAllowedPermissionsOf(Role $role): Collection;

    /**
     * Get only directly allowed permissions of specified role
     */
    abstract public function getDirectlyAllowedPermissionsOf(Role $role): Collection;

    /**
     * Get only indirectly allowed permissions of specified role
     */
    abstract public function getIndirectlyAllowedPermissionsOf(Role $role): Collection;

    /**
     * Get only forbidden permissions (including both directly and indirectly forbidden permissions) of specified role
     */
    abstract public function getForbiddenPermissionsOf(Role $role): Collection;

    /**
     * Get only directly forbidden permissions of specified role
     */
    abstract public function getDirectlyForbiddenPermissionsOf(Role $role): Collection;

    /**
     * Get only indirectly forbidden permissions of specified role
     */
    abstract public function getIndirectlyForbiddenPermissionsOf(Role $role): Collection;

    /**
     * Get all roles (including allowed,forbidden and indirect roles) that have the specified permission
     */
    abstract public function getAllRolesOf(Permission $permission): Collection;

    /**
     * Get only allowed roles (including both directly and indirectly allowed roles) that have the specified permission
     */
    abstract public function getAllowedRolesOf(Permission $permission): Collection;

    /**
     * Get only directly allowed roles that have the specified permission
     */
    abstract public function getDirectlyAllowedRolesOf(Permission $permission): Collection;

    /**
     * Get only indirectly allowed roles that have the specified permission
     */
    abstract public function getIndirectlyAllowedRolesOf(Permission $permission): Collection;

    /**
     * Get only forbidden roles (including both directly and indirectly forbidden roles) that have the specified permission
     */
    abstract public function getForbiddenRolesOf(Permission $permission): Collection;

    /**
     * Get only directly forbidden roles that have the specified permission
     */
    abstract public function getDirectlyForbiddenRolesOf(Permission $permission): Collection;

    /**
     * Get only indirectly forbidden roles that have the specified permission
     */
    abstract public function getIndirectlyForbiddenRolesOf(Permission $permission): Collection;

    /**
     * Check if a role can do a task by the specifying permission
     */
    abstract public function canRoleDo(Role $role, Permission $permission): bool;
}
