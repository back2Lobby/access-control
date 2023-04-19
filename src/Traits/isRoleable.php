<?php

namespace Back2Lobby\AccessControl\Traits;

use Back2Lobby\AccessControl\Exceptions\InvalidPermissionException;
use Back2Lobby\AccessControl\Facades\AccessControlFacade;
use Back2Lobby\AccessControl\Models\Permission;
use Back2Lobby\AccessControl\Models\Role;
use Back2Lobby\AccessControl\Models\RoleUser;
use Back2Lobby\AccessControl\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;

trait isRoleable
{
    public function roles(): MorphToMany
    {
        return $this->morphToMany(Role::class, 'roleable', 'role_user');
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
            return User::whereCan($permission, $this, $includeIndirectUsers);
        } else {
            throw new InvalidPermissionException("Provided permission $permission cannot be validated because its either invalid or not found in database");
        }
    }

    /**
     * Get all the users having any role on this roleable directly
     */
    public function users(): Builder
    {
        return User::select('users.*')->joinSub(RoleUser::where('roleable_id', $this->id)->where('roleable_type', $this::class), 'matched_role_user', function ($join) {
            $join->on('users.id', '=', 'matched_role_user.user_id');
        });
    }

    /**
     * Get all the RoleUser models for this roleable with direct users and roles
     *
     * @return Collection List of users with their assigned role names
     */
    public function usersWithRoles(): Collection
    {
        return User::select('users.*', 'roles.id as role_id')
            ->join('role_user', 'users.id', '=', 'role_user.user_id')
            ->join('roles', 'role_user.role_id', '=', 'roles.id')
            ->where('role_user.roleable_id', $this->id)
            ->where('role_user.roleable_type', $this::class)
            ->orderBy('users.id')
            ->get()
            ->groupBy('id')
            ->map(function ($group) {
                $user = $group->first();
                $roles = $group->pluck('role_id');
                $user->roles = $roles->map(fn ($r) => AccessControlFacade::getStore()->getRole($r))->filter(fn ($r) => ! is_null($r));
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
    public static function whereUserCan(User $user, Permission|string $permission): Builder
    {

        // getting a valid permission first
        if ($permission = AccessControlFacade::getStore()->getPermission($permission)) {

            // get all the roles that are allowed for this permission
            $allowedRoles = AccessControlFacade::getStore()->getAllowedRolesOf($permission);

            // no need to go any further if no role can have this permission
            if ($allowedRoles->count() <= 0) {
            return static::whereIn('id', []);
            }

            // now if there is any role that can have this permission, we will match them with roles that the user have
            $roles = RoleUser::where('user_id', $user->id)
                        ->whereIn('role_id', $allowedRoles->pluck('id'))
                        ->where('roleable_type', static::class)->where('roleable_id', '!=', 0)->select(['roleable_id as id'])->get()->pluck('id');

            return static::whereIn('id', $roles);
        } else {
            throw new InvalidPermissionException("Provided permission $permission cannot be validated because its either invalid or not found in database");
        }
    }
}
