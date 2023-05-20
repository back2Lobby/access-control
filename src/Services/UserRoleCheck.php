<?php

namespace Back2Lobby\AccessControl\Services;

use Back2Lobby\AccessControl\Exceptions\InvalidAttributesException;
use Back2Lobby\AccessControl\Exceptions\InvalidRoleableException;
use Back2Lobby\AccessControl\Exceptions\InvalidRoleException;
use Back2Lobby\AccessControl\Facades\AccessControlFacade;
use Back2Lobby\AccessControl\Models\Role;
use Back2Lobby\AccessControl\Stores\Abstracts\CacheStoreBase;
use Illuminate\Database\Eloquent\Model;

class UserRoleCheck
{
    public function __construct(private readonly CacheStoreBase $store, private readonly Model $user)
    {
    }

    private function checkRole(Model $role, Model $roleable = null): bool
    {
        if (AccessControlFacade::isAuthUser($this->user)) {
            $assignedRoles = AccessControlFacade::getAssignedRoles()
                ->filter(fn ($assignedRole) => $assignedRole->role_id === $role->id);

            if ($roleable) {
                $assignedRoles = $assignedRoles->filter(
                    fn ($assignedRole) => $assignedRole->roleable_type === $roleable::class &&
                        $assignedRole->roleable_id === $roleable->id
                );
            }

            return $assignedRoles->count() > 0;
        } else {
            $roleQuery = $this->user->belongsToMany(Role::class, 'assigned_roles')->where('name', $role->name);

            //now get the role with given roleable if asked
            if ($roleable) {
                $roleQuery = $roleQuery->wherePivot('roleable_type', $roleable::class)->wherePivot('roleable_id', $roleable->id);
            }

            return $roleQuery->exists();
        }
    }

    /**
     * Check if the user have given role on the specified roleable e.g check if user is a manager in given company
     *
     *
     * @throws InvalidRoleException|InvalidRoleableException
     */
    public function a(Role|string $role, Model $roleable = null): bool
    {
        if ($role = $this->store->getRole($role)) {
            // make sure the role supports the roleable
            $roleable = Role::getValidRoleable($role, $roleable);

            return $this->checkRole($role, $roleable);
        } else {
            throw new InvalidRoleException('Provided role cannot be validated because its either invalid or not found in database');
        }
    }

    /**
     * Check if the user have given role
     *
     * @throws InvalidRoleException|InvalidRoleableException
     */
    public function an(Role|string $role, Model $roleable = null): bool
    {
        return $this->a($role, $roleable);
    }

    /**
     * Check if the user doesn't have given role
     *
     * @throws InvalidRoleException|InvalidRoleableException
     */
    public function notA(Role|string $role, Model $roleable = null): bool
    {
        return $this->a($role, $roleable) === false;
    }

    /**
     * Check if the user doesn't have given role
     *
     * @throws InvalidRoleException|InvalidRoleableException
     */
    public function notAn(Role|string $role, Model $roleable = null): bool
    {
        return $this->notA($role, $roleable);
    }

    /**
     * check if user have all the given roles
     *
     * **NOTE**: it doesn't check roleables for the roles
     *
     * @param  array<int,string>  $roles array of role names as string
     *
     * @throws InvalidAttributesException
     */
    public function all(array $roles): bool
    {
        if ($this->isValidRolesArray($roles)) {
            if (AccessControlFacade::isAuthUser($this->user, true)) {
                $assignedRoleIds = AccessControlFacade::getAssignedRoles()
                                    ->map(fn ($assignedRole) => $assignedRole->role_id);

                return count($roles) === count($assignedRoleIds) &&
                    collect($roles)->every(function ($roleName) use ($assignedRoleIds) {
                    if ($role = AccessControlFacade::getRole($roleName)) {
                        return in_array($role->id, $assignedRoleIds);
                    }

                    return false;
                });
            } else {
                return $this->user->belongsToMany(Role::class, 'assigned_roles')->whereIn('name', $roles)->count() === count($roles);
            }
        }

        return false;
    }

    /**
     * check if user have at least one of the given roles
     *
     * **NOTE**: it doesn't check roleables for the roles
     *
     * @param  array<int,string>  $roles array of role names as string
     *
     * @throws InvalidAttributesException
     */
    public function any(array $roles): bool
    {
        if ($this->isValidRolesArray($roles)) {
            if (AccessControlFacade::isAuthUser($this->user, true)) {
                $assignedRoleIds = AccessControlFacade::getAssignedRoles()
                    ->map(fn ($assignedRole) => $assignedRole->role_id);

                return collect($roles)->some(function ($roleName) use ($assignedRoleIds) {
                        if ($role = AccessControlFacade::getRole($roleName)) {
                            return in_array($role->id, $assignedRoleIds);
                        }

                        return false;
                    });
            } else {
                return $this->user->belongsToMany(Role::class, 'assigned_roles')->whereIn('name', $roles)->count() > 0;
            }
        }

        return false;
    }

    private function isValidRolesArray(array $roles): bool
    {
        if (count($roles) > 0 && array_is_list($roles)) {
            if (count(array_filter($roles, fn ($r) => is_string($r))) === count($roles)) {
                return true;
            } else {
                throw new InvalidAttributesException('Only array of string role names is allowed');
                }
        }

        return false;
    }
}
