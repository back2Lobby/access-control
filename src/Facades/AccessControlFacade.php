<?php

namespace Back2Lobby\AccessControl\Facades;

use App\Model\User;
use Back2Lobby\AccessControl\Models\Role;
use Back2Lobby\AccessControl\Service\AllowPermission;
use Back2Lobby\AccessControl\Service\AssignRole;
use Back2Lobby\AccessControl\Service\DisallowPermission;
use Back2Lobby\AccessControl\Service\ForbidPermission;
use Back2Lobby\AccessControl\Service\RetractRole;
use Back2Lobby\AccessControl\Service\RolePermissionCheck;
use Back2Lobby\AccessControl\Service\UserPermissionCheck;
use Back2Lobby\AccessControl\Service\UserRoleCheck;
use Back2Lobby\AccessControl\Store\Contracts\Storable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;

/**
 * @method static AllowPermission allow(Role|string $role)
 * @method static DisallowPermission disallow(Role|string $role)
 * @method static ForbidPermission forbid(Role|string $role)
 * @method static AssignRole assign(Role|string $role, ?Model $roleable = null)
 * @method static RetractRole retract(Role|string $role, ?Model $roleable = null)
 * @method static UserRoleCheck is(User $user)
 * @method static UserPermissionCheck|null canUser(User $user = null,$throwException = false)
 * @method static bool resetUser(User $user)
 * @method static bool resetRole(Role|string $role)
 * @method static Storable getStore()
 *
 * @see \Back2Lobby\AccessControl\Service\AccessControlService;
 */
class AccessControlFacade extends Facade
{
	protected static function getFacadeAccessor()
	{
		return "access-control";
	}
}
