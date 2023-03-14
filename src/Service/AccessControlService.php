<?php

namespace Back2Lobby\AccessControl\Service;

use Back2Lobby\AccessControl\Exceptions\InvalidRoleException;
use Back2Lobby\AccessControl\Exceptions\InvalidUserException;
use Back2Lobby\AccessControl\Models\Role;
use Back2Lobby\AccessControl\Store\Contracts\Storable;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AccessControlService
{
    private static Storable $store;

    public function __construct(Storable $store){

        static::$store = $store;

    }

    public static function getStore(): Storable
    {
        return static::$store;
    }

	/**
	 * Allow a role to do something by assigning a task by passing `App\Models\Role::class` object or just name of the role
	 *
	 * @throws InvalidRoleException if given role is invalid or not found in database
	 */
	public static function allow(Role|string $role): AllowPermission
	{
		if ($role = static::getStore()->getRole($role)) {
			return new AllowPermission(static::getStore(),$role);
		} else {
			throw new InvalidRoleException("Provided role $role cannot be validated because its either invalid or not found in database");
		}
	}

	/**
	 * Allow a role to do something by assigning a task by passing `App\Models\Role::class` object or just name of the role
	 *
	 * @throws InvalidRoleException if given role is invalid or not found in database
	 */
	public static function disallow(Role|string $role): DisallowPermission
	{
        if ($role = static::getStore()->getRole($role)) {
			return new DisallowPermission(static::getStore(),$role);
		} else {
			throw new InvalidRoleException("Provided role cannot be validated because its either invalid or not found in database");
		}
	}

	/**
	 * Forbid a role to do something by assigning a task by passing `App\Models\Role::class` object or just name of the role
	 *
	 * @throws InvalidRoleException if given role is invalid or not found in database
	 */
	public static function forbid(Role|string $role): ForbidPermission
	{
        if ($role = static::getStore()->getRole($role)) {
			return new ForbidPermission(static::getStore(),$role);
		} else {
			throw new InvalidRoleException("Provided role cannot be validated because its either invalid or not found in database");
		}
	}

	/**
	 * Assign a given role for given roleable model
	 *
	 */
	public static function assign(Role|string $role, ?Model $roleable = null): AssignRole
	{
        if ($role = static::getStore()->getRole($role)) {
			return new AssignRole($role, $roleable);
		} else {
			throw new InvalidRoleException("Provided role cannot be validated because its either invalid or not found in database");
		}
	}


	/**
	 * Retract a given role from given roleable model
	 *
	 */
	public static function retract(Role|string $role, ?Model $roleable = null): RetractRole
	{
        if ($role = static::getStore()->getRole($role)) {
			return new RetractRole($role, $roleable);
		} else {
			throw new InvalidRoleException("Provided role cannot be validated because its either invalid or not found in database");
		}
	}

	/**
	 * Check if a user have a role
	 *
	 */
	public static function is(User $user): UserRoleCheck
	{
		if ($user->id) {
			return new UserRoleCheck($user);
		} else {
			throw new InvalidRoleException("Provided role cannot be validated because its either invalid or not found in database");
		}
	}

    /**
     * Check if the given role have specific permission or not
     * - returns `null` when no user or role is available
     *
     *
     */
    public static function canRole(Role|string $role,$throwException = false): RolePermissionCheck|null
    {
        if ($role = static::getStore()->getRole($role)) {
            return new RolePermissionCheck(static::getStore(),$role);
        }else{
            if($throwException){
                throw new \RuntimeException("Invalid Role Provided");
            }else{
                return null;
            }
        }
    }

    /**
     * Check if the given user have specific permission or not
     * - if no argument is passed then it performs the check on authenticated user
     * - returns `null` when no user is available
     *
     *
     * @throws InvalidUserException
     */
	public static function canUser(User $user = null,$throwException = false): UserPermissionCheck|null
	{
		if (is_null($user)) {
            $user = auth()->user();
		}

        if ($user instanceof User && $user->id) {
			return new UserPermissionCheck(static::getStore(),$user);
		}else{
            if($throwException){
                throw new InvalidUserException("Invalid User Provided");
            }else{
                return null;
            }
        }
	}


    /**
     * Reset given user.
     * - returns false if operation wasn't successful
     *
     * @param User $user
     * @return bool
     */
    public static function resetUser(User $user): bool
	{
		if ($user->id) {
			return DB::table("role_user")->where("user_id", $user->id)->delete() >= 0;
		}

		return false;
	}

    /**
     * Reset given role.
     * - returns false if operation wasn't successful
     *
     * @param Role|string $role
     * @return bool
     */
    public static function resetRole(Role|string $role): bool
    {
        if ($role = static::getStore()->getRole($role)) {
            return DB::table("permission_role")->where("user_id", $role->id)->delete() >= 0;
        }

        return false;
    }
}
