<?php

namespace Back2Lobby\AccessControl\Contracts;

use Back2Lobby\AccessControl\Exceptions\InvalidPermissionException;
use Back2Lobby\AccessControl\Models\Permission;
use Back2Lobby\AccessControl\Models\Role;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface AccessControlUser
{
    /**
     * Get all the roles of this user
     *
     * @returns Collection
     */
    public function roles(): Collection;

    /**
     * Get all the permission of this user from any role combined
     *
     * @returns Collection
     */
    public function permissions(): Collection;

    public function assign(Role|string $role, Model $roleable = null): bool;

    /**
     * Get users having specified role for the specified roleable
     * - if nothing is given as roleable in 2nd argument then it will only check roles that don't have any roleables attached
     */
    public static function whereIs(Role|string $role, Model $roleable = null): Builder;

    /**
     * Get all the users with specified permission on a given roleable
     *
     * @param  Permission|string  $permission Permission to check
     * @param  Model|null  $roleable Target roleable model e.g. company
     * @param  bool  $includeIndirectRoles Include the users who have the permission indirectly like super-admin
     *
     * @throws InvalidPermissionException
     *
     * @returns \Illuminate\Database\Eloquent\Builder
     */
    public static function whereCan(Permission|string $permission, Model $roleable = null, bool $includeIndirectRoles = false): \Illuminate\Database\Eloquent\Builder;
}
