<?php

namespace Back2Lobby\AccessControl\Service\Contracts;

use Back2Lobby\AccessControl\Exceptions\InvalidAttributesException;
use Back2Lobby\AccessControl\Exceptions\InvalidPermissionException;
use Back2Lobby\AccessControl\Exceptions\InvalidRoleException;
use Back2Lobby\AccessControl\Exceptions\InvalidUserException;
use Back2Lobby\AccessControl\Exceptions\UpdateProcessFailedException;
use Back2Lobby\AccessControl\Models\Permission;
use Back2Lobby\AccessControl\Models\Role;
use Back2Lobby\AccessControl\Models\User;
use Back2Lobby\AccessControl\Service\AllowPermission;
use Back2Lobby\AccessControl\Service\AssignRole;
use Back2Lobby\AccessControl\Service\DisallowPermission;
use Back2Lobby\AccessControl\Service\ForbidPermission;
use Back2Lobby\AccessControl\Service\RetractRole;
use Back2Lobby\AccessControl\Service\RolePermissionCheck;
use Back2Lobby\AccessControl\Service\UserPermissionCheck;
use Back2Lobby\AccessControl\Service\UserRoleCheck;
use Back2Lobby\AccessControl\Store\Abstracts\Storable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

interface Accessable
{
    /**
     * Get the store instance
     */
    public function getStore(): Storable;

    /**
     * Create a new role
     *
     * @throws QueryException|InvalidAttributesException|ValidationException
     */
    public function createRole(array $attributes): Role;

    /**
     * Create many roles
     *
     *
     * @throws QueryException|InvalidAttributesException|ValidationException
     */
    public function createManyRoles(array $roles): bool;

    /**
     * Update a role
     *
     *
     * @throws InvalidRoleException|InvalidAttributesException|UpdateProcessFailedException
     */
    public function updateRole(Role $role, array $attributes): Role;

    /**
     * Create a new permission
     *
     * @throws QueryException|InvalidAttributesException|ValidationException
     */
    public function createPermission(array $attributes): Permission;

    /**
     * Create many permissions
     *
     *
     * @throws QueryException|InvalidAttributesException|ValidationException
     */
    public function createManyPermissions(array $permissions): bool;

    /**
     * Update a permission
     *
     *
     * @throws InvalidPermissionException|InvalidAttributesException|UpdateProcessFailedException
     */
    public function updatePermission(Permission $permission, array $attributes): Permission;

    /**
     * Delete a role
     */
    public function deleteRole(Role|string|int $role): bool;

    /**
     * Delete a permission
     */
    public function deletePermission(Permission|string|int $permission): bool;

    /**
     * Grant a permission to a role
     *
     * @throws InvalidRoleException if given role is invalid or not found in database
     */
    public function allow(Role|string $role): AllowPermission;

    /**
     * Take back the granted permission from a role
     *
     * @throws InvalidRoleException if given role is invalid or not found in database
     */
    public function disallow(Role|string $role): DisallowPermission;

    /**
     * Forbid a permission for a role
     *
     * @throws InvalidRoleException if given role is invalid or not found in database
     */
    public function forbid(Role|string $role): ForbidPermission;

    /**
     * Assign a given role for given roleable model
     *
     * @throws InvalidRoleException
     */
    public function assign(Role|string $role, ?Model $roleable = null): AssignRole;

    /**
     * Retract a given role from given roleable model
     *
     * @throws InvalidRoleException
     */
    public function retract(Role|string $role, ?Model $roleable = null): RetractRole;

    /**
     * Check if a user have a role
     *
     * @throws InvalidUserException
     */
    public function is(User $user): UserRoleCheck;

    /**
     * Check if the given role have specific permission or not
     * - returns `null` when no user or role is available
     *
     * @throws InvalidRoleException
     */
    public function canRole(Role|string $role): RolePermissionCheck;

    /**
     * Check if the given user have specific permission or not
     *
     * @throws InvalidUserException
     */
    public function canUser(User $user): UserPermissionCheck;

    /**
     * Reset given user.
     * - returns false if operation wasn't successful
     *
     * @throws InvalidUserException
     */
    public function resetUser(User $user): bool;

    /**
     * Reset given role.
     * - returns false if operation wasn't successful
     *
     * @throws InvalidRoleException
     */
    public function resetRole(Role|string $role): bool;
}
