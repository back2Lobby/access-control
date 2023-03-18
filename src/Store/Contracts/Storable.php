<?php

namespace Back2Lobby\AccessControl\Store\Contracts;

use Back2Lobby\AccessControl\Models\Permission;
use Back2Lobby\AccessControl\Models\Role;
use Back2Lobby\AccessControl\Store\Enumerations\SyncFlag;
use Illuminate\Support\Collection;

interface Storable
{
    /**
     * Sync all roles and permissions with database
     *
     * @param SyncFlag $flag specify what should be synced
     * @return void
     */
    public function sync(SyncFlag $flag = SyncFlag::SyncAll): void;


    /**
     * Cache this object
     *
     * @return void
     */
    public function cache(): void;

    /**
     * Clear the cached object
     *
     * @return void
     */
    public function clearCache(): void;


    /**
     * Get all the roles available
     *
     * @return Collection
     */
    public function getRoles(): Collection;

    /**
     * Get all the permissions available
     *
     * @return Collection
     */
    public function getPermissions(): Collection;

    /**
     * Get all the maps available
     *
     * @return Collection
     */
    public function getMaps(): Collection;

    /**
     * Get role if it exists
     *
     * @param Role|string|int $role
     * @return Role|null
     */
    public function getRole(Role|string|int $role): Role|null;

    /**
     * Get permission if it exists
     *
     * @param Permission|string|int $permission
     * @return Permission|null
     */
    public function getPermission(Permission|string|int $permission): Permission|null;

    /**
     * Get all permissions of specified role
     *
     * @param Role $role
     * @return Collection
     */
    public function getAllPermissionsOf(Role $role): Collection;

    /**
     * Get only allowed permissions of specified role
     *
     * @param Role $role
     * @return Collection
     */
    public function getAllowedPermissionsOf(Role $role): Collection;

    /**
     * Get only indirect permissions of specified role
     *
     * @param Role $role
     * @return Collection
     */
    public function getIndirectPermissionsOf(Role $role): Collection;

    /**
     * Get only forbidden permissions of specified role
     *
     * @param Role $role
     * @return Collection
     */
    public function getForbiddenPermissionsOf(Role $role): Collection;

    /**
     * Get all roles that have specified permission
     *
     * @param Permission $permission
     * @return Collection
     */
    public function getAllRolesOf(Permission $permission): Collection;

    /**
     * Get only allowed roles that have specified permission
     *
     * @param Permission $permission
     * @return Collection
     */
    public function getAllowedRolesOf(Permission $permission): Collection;

    /**
     * Get only indirect roles that have specified permission
     *
     * @param Permission $permission
     * @return Collection
     */
    public function getIndirectRolesOf(Permission $permission): Collection;

    /**
     * Get only forbidden roles that have specified permission
     *
     * @param Permission $permission
     * @return Collection
     */
    public function getForbiddenRolesOf(Permission $permission): Collection;

    /**
     * Check if a role can do a task by specifying permission
     *
     * @param Role $role
     * @param Permission $permission
     * @return bool
     */
    public function canRoleDo(Role $role, Permission $permission): bool;
}
