<?php

namespace Back2Lobby\AccessControl\Models;

use Back2Lobby\AccessControl\Contracts\AccessControlUser;
use Back2Lobby\AccessControl\Exceptions\InvalidPermissionException;
use Back2Lobby\AccessControl\Exceptions\InvalidRoleableException;
use Back2Lobby\AccessControl\Exceptions\InvalidRoleException;
use Back2Lobby\AccessControl\Exceptions\InvalidUserException;
use Back2Lobby\AccessControl\Facades\AccessControlFacade;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Collection;

class User extends Authenticatable implements AccessControlUser
{
    use HasFactory;

    public function roles(): Collection
    {
        return $this->belongsToMany(Role::class)->using(RoleUser::class)->get(['id'])->map(function ($role) {
            return AccessControlFacade::getRole($role->id);
        });
    }

    /**
     * Get all the directly & indirectly allowed permissions of this user from any role combined
     *
     * @returns Collection
     */
    public function permissions(): Collection
    {
        // get all the roles of this users
        $roles = RoleUser::where('user_id', $this->id)->get(['role_id'])->pluck('role_id');

        // get all the permission of each role
        $permissions = $roles->map(fn ($r) => AccessControlFacade::getRole($r))
            ->filter(fn ($r) => ! is_null($r))
            ->map(fn ($r) => AccessControlFacade::getAllowedPermissionsOf($r));

        return $permissions->flatten()->unique();
    }

    public function assign(Role|string $role, Model $roleable = null): bool
    {
        return AccessControlFacade::assign($role, $roleable)->to($this);
    }

    /**
     * Get users having specified role for the specified roleable
     * - if nothing is given as roleable in 2nd argument then it will only check roles that don't have any roleables attached after validation
     *
     * @throws InvalidRoleException|InvalidRoleableException
     */
    public static function whereIs(Role|string $role, Model $roleable = null): Builder
    {
        if ($role = AccessControlFacade::getRole($role)) {

            $roleable = Role::getValidRoleable($role, $roleable);

            return User::select('users.*')
                    ->join('role_user', 'role_user.user_id', '=', 'users.id')
                    ->where(function ($q) use ($role, $roleable) {
                        $q->where('role_id', $role->id);
                        if (isset($roleable->id)) {
                            $q->where('roleable_id', $roleable->id)->where('roleable_type', $roleable::class);
                        } else {
                            $q->where('roleable_id', 0)->where('roleable_type', '');
                        }
                    });
        } else {
            throw new InvalidRoleException('Provided role cannot be validated because its either invalid or not found in database');
        }
    }

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
    public static function whereCan(Permission|string $permission, Model $roleable = null, bool $includeIndirectRoles = false): \Illuminate\Database\Eloquent\Builder
    {
        if ($permission = AccessControlFacade::getPermission($permission)) {

            $users = User::query();

            // get all roles that are directly allowed for this permission
            $roles = AccessControlFacade::getDirectlyAllowedRolesOf($permission);

            // making sure that we get only roles that allow this roleable
            $roles = $roles->filter(function ($r) use ($roleable) {
                if (is_null($roleable)) {
                    return is_null($r->roleables) || (is_array($r->roleables) && empty($r->roleables));
                } else {
                    return is_array($r->roleables) && in_array($roleable::class, $r->roleables);
                }
            });

            if ($includeIndirectRoles) {
                $roles = $roles->concat(AccessControlFacade::getIndirectlyAllowedRolesOf($permission));
            }

            // if no roles available then no need to go any further
            if ($roles->count() <= 0) {
            return $users->whereNull('id');
            }

            // get matching user roles
            $usersQuery = RoleUser::whereIn('role_id', $roles->pluck('id'));

            // filter it based on roleable
            $usersQuery->where(function ($q) use ($roleable, $includeIndirectRoles) {

                if ($roleable && $roleable->id) {
                    $q->where(function ($qq) use ($roleable) {
                        $qq->where('roleable_type', $roleable::class)
                            ->where('roleable_id', $roleable->id);
                    });
                }

                if ($includeIndirectRoles) {
                    $q->orWhere(function ($qq) {
                        $qq->where('roleable_type', '')
                            ->where('roleable_id', 0);
                    });
                }
            });

            // return users by joining it with users table
            return User::distinct()->select('users.*')->joinSub(
                $usersQuery,
                'matched_role_user',
                fn ($j) => $j->on('matched_role_user.user_id', '=', 'users.id')
            );
        } else {
            throw new InvalidPermissionException('Provided permission cannot be validated because its either invalid or not found in database');
        }
    }

    /**
     * Get user if it's valid
     *
     * @throws InvalidUserException if given role is invalid or not found in database
     */
    public static function getValidUser(User|int $user): User
    {
        if (is_int($user)) {
            $user = User::find($user);
        }

        if ($user instanceof User && $user->id) {
            return $user;
        } else {
            throw new InvalidUserException('Provided user cannot be validated because its either invalid or not found in database');
        }
    }
}
