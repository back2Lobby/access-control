<?php

namespace Back2Lobby\AccessControl\Service;

use Back2Lobby\AccessControl\Exceptions\InvalidAttributesException;
use Back2Lobby\AccessControl\Exceptions\InvalidRoleableException;
use Back2Lobby\AccessControl\Exceptions\InvalidRoleException;
use Back2Lobby\AccessControl\Models\Role;
use Back2Lobby\AccessControl\Models\RoleUser;
use Back2Lobby\AccessControl\Models\User;
use Back2Lobby\AccessControl\Store\Abstracts\Storable;
use Illuminate\Database\Eloquent\Model;

class UserRoleCheck
{
    public function __construct(private readonly Storable $store, private readonly User $user)
    {
    }

    /**
     * Check if the user have given role on the specified roleable e.g check if user is a manager in given company
     * - if no roleable is given then it will return `true` if the user has the role for any roleable
     *
     * @throws InvalidRoleException|InvalidRoleableException
     */
    public function a(Role|string $role, Model $roleable = null): bool
    {
        if ($role = $this->store->getRole($role)) {
            // make sure the role supports the roleable
            $roleable = Role::getValidRoleable($role, $roleable);

            $roleQuery = $this->user->belongsToMany(Role::class)->using(RoleUser::class)->where('name', $role->name);

            //now get the role with given roleable if asked
            if ($roleable) {
                $roleQuery = $roleQuery->wherePivot('roleable_type', $roleable::class)->wherePivot('roleable_id', $roleable->id);
            }

            return $roleQuery->exists();
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
     * @param  array<int,string>  $roles array of role names as string
     *
     * @throws InvalidAttributesException
     */
    public function all(array $roles): bool
    {
        if (count($roles) > 0 && array_is_list($roles)) {

            if (count(array_filter($roles, fn ($r) => is_string($r))) === count($roles)) {
                return $this->user->belongsToMany(Role::class)->using(RoleUser::class)->whereIn('name', $roles)->count() === count($roles);
            } else {
                throw new InvalidAttributesException('Only array of string role names is allowed');
            }
        }

        return false;
    }

    /**
     * check if user have at least one of the given roles
     *
     * @param  array<int,string>  $roles array of role names as string
     *
     * @throws InvalidAttributesException
     */
    public function any(array $roles): bool
    {
        if (count($roles) > 0 && array_is_list($roles)) {
            if (count(array_filter($roles, fn ($r) => is_string($r))) === count($roles)) {
                return $this->user->belongsToMany(Role::class)->using(RoleUser::class)->whereIn('name', $roles)->count() > 0;
            } else {
                throw new InvalidAttributesException('Only array of string role names is allowed');
            }
        }

        return false;
    }
}
