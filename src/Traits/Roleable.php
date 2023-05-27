<?php

namespace Back2Lobby\AccessControl\Traits;

use Back2Lobby\AccessControl\Exceptions\InvalidPermissionException;
use Back2Lobby\AccessControl\Exceptions\InvalidUserException;
use Back2Lobby\AccessControl\Facades\AccessControlFacade as AccessControl;
use Back2Lobby\AccessControl\Models\AssignedRole;
use Back2Lobby\AccessControl\Models\Permission;
use Back2Lobby\AccessControl\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;

trait Roleable
{
    public function roles(): MorphToMany
    {
        return $this->morphToMany(Role::class, 'roleable', 'assigned_roles');
    }

    /**
     * Get all the users for this roleables having given permission e.g. get all users who can delete this company
     *
     * @param  bool  $includeIndirectUsers Include users who were given permissions indirectly also (e.g. super-admin or admin)
     *
     * @throws InvalidPermissionException
     */
    public function usersHavingPermission(Permission|string $permission, bool $includeIndirectUsers = false): Builder
    {
        $permission = $permission instanceof Permission ? $permission->name : $permission;

        if (is_string($permission)) {
            return AccessControl::getAuthUserModel()::whereCan($permission, $this, $includeIndirectUsers);
        } else {
            throw new InvalidPermissionException("Provided permission $permission cannot be validated because its either invalid or not found in database");
        }
    }

    /**
     * Get all the users having any role on this roleable directly
     */
    public function users(): Builder
    {
        return AccessControl::getAuthUserModel()::select(AccessControl::getAuthUserTable().'.*')->joinSub(AssignedRole::where('roleable_id', $this->id)->where('roleable_type', $this::class), 'matched_role_user', function ($join) {
            $join->on('users.id', '=', 'matched_role_user.user_id');
        });
    }

    /**
     * Get all the AssignedRole models for this roleable with direct users and roles
     *
     * @return Collection List of users with their assigned role names
     */
    public function usersWithRoles(): Collection
    {
        return AccessControl::getAuthUserModel()::select('users.*', 'roles.id as role_id')
            ->join('assigned_roles', 'users.id', '=', 'assigned_roles.user_id')
            ->join('roles', 'assigned_roles.role_id', '=', 'roles.id')
            ->where('assigned_roles.roleable_id', $this->id)
            ->where('assigned_roles.roleable_type', $this::class)
            ->orderBy('users.id')
            ->get()
            ->groupBy('id')
            ->map(function ($group) {
                $user = $group->first();
                $roles = $group->pluck('role_id');
                $user->roles = $roles->map(fn ($r) => AccessControl::getRole($r))->filter(fn ($r) => ! is_null($r));
                unset($user->role_id);

                return $user;
            })
            ->values();
    }

    /**
     * Get roleables where given user have given permission directly e.g. get all companies where user have specified permission
     *
     *
     *
     * @throws InvalidPermissionException
     */
    public static function whereUserCan(Model $user, Permission|string $permission): Builder
    {
        if (! AccessControl::isValidUser($user, true)) {
            throw new InvalidUserException('Given user is not valid');
        }

        // getting a valid permission first
        if ($permission = AccessControl::getPermission($permission)) {

            // get all the roles that are allowed for this permission
            $allowedRoles = AccessControl::getAllowedRolesOf($permission);

            // no need to go any further if no role can have this permission
            if ($allowedRoles->count() <= 0) {
                return static::whereIn('id', []);
            }

            // now if there is any role that can have this permission, we will match them with roles that the user have
            $roles = AssignedRole::where('user_id', $user->id)
                ->whereIn('role_id', $allowedRoles->pluck('id'))
                ->where('roleable_type', static::class)->where('roleable_id', '!=', 0)->select(['roleable_id as id'])->get()->pluck('id');

            return static::whereIn('id', $roles);
        } else {
            throw new InvalidPermissionException("Provided permission $permission cannot be validated because its either invalid or not found in database");
        }
    }
}
