<?php

namespace Back2Lobby\AccessControl\Service;

use Back2Lobby\AccessControl\Exceptions\InvalidRoleException;
use App\Models\User;
use Back2Lobby\AccessControl\Models\Role;
use Back2Lobby\AccessControl\Store\Contracts\Storable;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class UserRoleCheck
{
	public function __construct(private readonly Storable $store, private readonly User $user)
	{
	}

	/**
	 * Check if the user have given role on the specified roleable e.g check if user is a manager in given company
     * - if no roleable is given then it will return `true` if the user has the role for any roleable
	 *
	 */
	public function a(Role|string $role, Model $roleable = null): bool
	{
		if ($role = $this->store->getRole($role)) {
            // if roleable is given then make sure the role supports it
            if(! (is_null($roleable) ||
                ($roleable->id && is_array($role->roleables) && in_array($roleable,$role->roleables))))
                return false;

			$roleQuery = $this->user->roles()->where("name", $role->name);

            //now get the role with given roleable if asked
            if($roleable){
                $roleQuery = $roleQuery->wherePivot("roleable_type", $roleable::class)->wherePivot("roleable_id", $roleable->id);
            }

            return $roleQuery->exists();
		} else {
			throw new InvalidRoleException("Provided role cannot be validated because its either invalid or not found in database");
		}
	}

	/**
	 * Check if the user have given role
	 *
	 */
	public function an(Role|string $role, Model $roleable = null): bool
	{
		return $this->a($role, $roleable);
	}

	/**
	 * Check if the user doesn't have given role
	 *
	 */
	public function notA(Role|string $role, Model $roleable = null): bool
	{
		return $this->a($role, $roleable) === false;
	}

	/**
	 * Check if the user doesn't have given role
	 *
	 */
	public function notAn(Role|string $role, Model $roleable = null): bool
	{
		return $this->notA($role, $roleable);
	}

	/**
	 * check if user have all the given roles
	 *
	 */
	public function all(array $roles): bool
	{
		if (count($roles) > 0 && array_is_list($roles)) {

			if (count(array_filter($roles, fn ($r) => is_string($r))) === count($roles)) {
				return $this->user->roles()->whereIn("name", $roles)->count() === count($roles);
			} else {
				throw new RuntimeException("Only array of string role names is allowed");
			}
		}

        return false;
	}

	/**
	 * check if user have at least one of the given roles
	 *
	 */
	public function any(array $roles): bool
	{
		if (count($roles) > 0 && array_is_list($roles)) {
			if (count(array_filter($roles, fn ($r) => is_string($r))) === count($roles)) {
				return $this->user->roles()->whereIn("name", $roles)->count() > 0;
			} else {
				throw new RuntimeException("Only array of string role names is allowed");
			}
		}

        return false;
	}
}
