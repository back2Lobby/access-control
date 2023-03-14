<?php

namespace Back2Lobby\AccessControl\Service;

use Back2Lobby\AccessControl\Exceptions\InvalidRoleableException;
use Back2Lobby\AccessControl\Exceptions\InvalidUserException;
use App\Models\User;
use Back2Lobby\AccessControl\Models\Role;
use Back2Lobby\AccessControl\Service\Contracts\Retractable;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Log;

class RetractRole implements Retractable
{
	public function __construct(private readonly Role $role, private Model|null $roleable = null)
	{
	}

	public function from(User $user): bool
	{
		if ($user = $this->getValidUser($user)) {
			try {
				if (is_null($this->roleable)) {
					return DB::table("role_user")->where(["user_id" => $user->id, "role_id" => $this->role->id, "roleable_id" => "", "roleable_type" => 0])->delete();
				} else if ($this->roleable = $this->getValidRoleable($this->roleable)) {
					return DB::table("role_user")->where([
						"user_id" => $user->id,
						"role_id" => $this->role->id,
						"roleable_id" => $this->roleable->id,
						"roleable_type" => $this->roleable::class
					])->delete();
				}
			} catch (QueryException $e) {
				Log::error("Couldn't retract role " . $this->role->name . " from the user $user->name :" . $e->getMessage());
			}
		}

		return false;
	}

	/**
	 * Get a guaranteed valid user model object
	 *
	 * @throws InvalidUserException if given role is invalid or not found in database
	 */
	private function getValidUser(User|int $user): User
	{
		if (is_integer($user)) {
			$user = User::find($user);
		}

		if ($user instanceof User && $user->id) {
			return $user;
		} else {
			throw new InvalidUserException("Provided user cannot be validated because its either invalid or not found in database");
		}
	}

	/**
	 * Get a guaranteed valid roleable model object
	 *
	 * @throws InvalidRoleableException if given role is invalid or not found in database
	 */
	private function getValidRoleable(): Model
	{
		if (is_array($this->role->roleables) && $this->roleable->id && in_array($this->roleable::class, $this->role->roleables)) {
			return $this->roleable;
		} else {
			throw new InvalidRoleableException("Provided roleable cannot be validated because its either invalid or not found");
		}
	}
}
