<?php

namespace Back2Lobby\AccessControl\Services;

use Back2Lobby\AccessControl\Facades\AccessControlFacade;
use Back2Lobby\AccessControl\Models\AssignedRole;
use Back2Lobby\AccessControl\Models\Permission;
use Back2Lobby\AccessControl\Stores\Abstracts\CacheStoreBase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UserPermissionCheck
{
    public function __construct(private readonly CacheStoreBase $store, private readonly Model $user)
    {
    }

    /**
     * Check if user have a specific permission for given roleable e.g. can user delete a company
     * - if no roleable is passed then it will check for only permissions for roles with no roleables (for security reasons)
     * - returns falls by default when permission is not found
     */
    public function do(Permission|string $permission, Model $roleable = null): bool
    {
        // no need to do anything if permission isn't available
        if (! $permission = $this->store->getPermission($permission)) {
            return false;
        }

        if (AccessControlFacade::isAuthUser($this->user)) {
            $assignedRoles = AccessControlFacade::getAssignedRoles();

            $suitableAssignedRoles = $assignedRoles->filter(function ($assignedRole) {
                return $assignedRole->roleable_id === 0 && $assignedRole->roleable_type === '';
            });

            if ($roleable && $roleable->id) {
                $suitableAssignedRoles = $suitableAssignedRoles->concat($assignedRoles->filter(function ($assignedRole) use ($roleable) {
                    return $assignedRole->roleable_id === $roleable->id && $assignedRole->roleable_type === $roleable::class;
                }));
            }

            $roleIds = $suitableAssignedRoles->map(function ($assignedRole) {
                return $assignedRole->role_id;
            });
        } else {

            $userColumnName = Str::singular(AccessControlFacade::getAuthUserTable()).'_id';

            //get all the roles of user from database
            $roles = AssignedRole::where($userColumnName, $this->user->getKey());

            // filter by roleable if its provided otherwise get all roles
            $roles->where(function ($q) use ($roleable) {
                if ($roleable && $roleable->id) {
                    $q->where(function ($qq) use ($roleable) {
                        $qq->where('roleable_type', $roleable::class)->where('roleable_id', $roleable->id);
                    });
                }

                // get all roles where there is no roleable, so we can figure out if user has special roles like admin
                $q->orWhere(function ($qq) {
                    $qq->where('roleable_type', '')->where('roleable_id', 0);
                });
            });

            // get by only columns that we need
            $roleIds = $roles->select(['role_id'])->get()->pluck('role_id');
        }

        // return true if any of the roles have the permission
        return $roleIds->some(function ($role) use ($permission, $roleable) {

            $role = $this->store->getRole($role);

            // validate the roles and if it's a special role like admin then skip validation
            if ($role && (is_null($role->roleables) || (is_array($role->roleables) && $roleable && in_array($roleable::class, $role->roleables)))) {
                return $this->store->canRoleDo($role, $permission);
            }

            return false;
        });
    }
}
