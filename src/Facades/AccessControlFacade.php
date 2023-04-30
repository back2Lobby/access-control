<?php

namespace Back2Lobby\AccessControl\Facades;

use Back2Lobby\AccessControl\Models\Permission;
use Back2Lobby\AccessControl\Models\Role;
use Back2Lobby\AccessControl\Models\User;
use Back2Lobby\AccessControl\Service\AllowPermission;
use Back2Lobby\AccessControl\Service\AssignRole;
use Back2Lobby\AccessControl\Service\DisallowPermission;
use Back2Lobby\AccessControl\Service\ForbidPermission;
use Back2Lobby\AccessControl\Service\RetractRole;
use Back2Lobby\AccessControl\Service\RolePermissionCheck;
use Back2Lobby\AccessControl\Service\UserPermissionCheck;
use Back2Lobby\AccessControl\Service\UserRoleCheck;
use Back2Lobby\AccessControl\Store\Abstracts\Storable;
use Back2Lobby\AccessControl\Store\Enumerations\SyncFlag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * - From AccessService
 *
 * @method static Role createRole(array $attributes)
 * @method static Permission createPermission(array $attributes)
 * @method static bool createManyRoles(array $roles)
 * @method static bool createManyPermissions(array $roles)
 * @method static Role updateRole(Role $role, array $attributes)
 * @method static Permission updatePermission(Permission $permission, array $attributes)
 * @method static bool deleteRole(Role|string|int $role)
 * @method static bool deletePermission(Permission|string|int $permission)
 * @method static AllowPermission allow(Role|string $role)
 * @method static DisallowPermission disallow(Role|string $role)
 * @method static ForbidPermission forbid(Role|string $role)
 * @method static AssignRole assign(Role|string $role, ?Model $roleable = null)
 * @method static RetractRole retract(Role|string $role, ?Model $roleable = null)
 * @method static UserRoleCheck is(User $user)
 * @method static RolePermissionCheck|null canRole(Role|string $role,$throwException = false)
 * @method static UserPermissionCheck|null canUser(User $user = null,$throwException = false)
 * @method static bool resetUser(User $user)
 * @method static bool resetRole(Role|string $role)
 * @method static Storable getStore()
 *
 * - From StoreService
 * @method static void sync(SyncFlag $flag = SyncFlag::SyncAll)
 * @method static void cache()
 * @method static void clearCache()
 * @method static void reset()
 * @method static Collection getRoles()
 * @method static Collection getPermissions()
 * @method static Collection getMap()
 * @method static Role|null getRole(Role|string|int $role)
 * @method static Permission|null getPermission(Permission|string|int $permission)
 * @method static Collection getAllPermissionsOf(Role $role)
 * @method static Collection getAllowedPermissionsOf(Role $role)
 * @method static Collection getDirectlyAllowedPermissionsOf(Role $role)
 * @method static Collection getIndirectlyAllowedPermissionsOf(Role $role)
 * @method static Collection getForbiddenPermissionsOf(Role $role)
 * @method static Collection getDirectlyForbiddenPermissionsOf(Role $role)
 * @method static Collection getIndirectlyForbiddenPermissionsOf(Role $role)
 * @method static Collection getAllRolesOf(Permission $permission)
 * @method static Collection getAllowedRolesOf(Permission $permission)
 * @method static Collection getDirectlyAllowedRolesOf(Permission $permission)
 * @method static Collection getIndirectlyAllowedRolesOf(Permission $permission)
 * @method static Collection getForbiddenRolesOf(Permission $permission)
 * @method static Collection getDirectlyForbiddenRolesOf(Permission $permission)
 * @method static Collection getIndirectlyForbiddenRolesOf(Permission $permission)
 * @method static bool canRoleDo(Role $role, Permission $permission)
 *
 * @see \Back2Lobby\AccessControl\Service\AccessService;
 * @see \Back2Lobby\AccessControl\Store\StoreService;
 */
class AccessControlFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'access-control';
    }
}
