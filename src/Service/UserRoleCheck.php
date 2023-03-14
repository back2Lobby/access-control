<?php

namespace Back2Lobby\AccessControl\Service;

use Back2Lobby\AccessControl\Exceptions\InvalidRoleException;
use App\Models\User;
use Back2Lobby\AccessControl\Models\Role;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class UserRoleCheck
{
	public function __construct(private readonly User $user)
	{
	}

	/**
	 * Check if the user have given role on the specified roleable e.g check if user is a manager in given company
	 *
	 */
	public function a(Role|string $role, Model $roleable = null): bool
	{
		$roleName = $role instanceof Role ? $role->name : $role;

		if (!is_null($roleName)) {
			$roleQuery = $this->user->roles()->where("name", $roleName);

			if ($roleable && $roleable->id) {
				// first make sure the roleable is from the allowed list
				$roleQuery = $roleQuery->whereJsonContains('roleables', $roleable::class);

				//now get the role with given roleable
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
	 * check if user have atleast one of the given roles
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
