<?php

namespace Back2Lobby\AccessControl\Facades;

use Back2Lobby\AccessControl\Models\Permission;
use Back2Lobby\AccessControl\Models\Role;
use Back2Lobby\AccessControl\Models\User;
use Back2Lobby\AccessControl\Services\AllowPermission;
use Back2Lobby\AccessControl\Services\AssignRole;
use Back2Lobby\AccessControl\Services\DisallowPermission;
use Back2Lobby\AccessControl\Services\ForbidPermission;
use Back2Lobby\AccessControl\Services\RetractRole;
use Back2Lobby\AccessControl\Services\RolePermissionCheck;
use Back2Lobby\AccessControl\Services\UserPermissionCheck;
use Back2Lobby\AccessControl\Services\UserRoleCheck;
use Back2Lobby\AccessControl\Stores\Abstracts\CacheStoreBase;
use Back2Lobby\AccessControl\Stores\Abstracts\SessionStoreBase;
use Back2Lobby\AccessControl\Stores\Enumerations\SyncFlag;
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
 * @method static CacheStoreBase getCacheStore()
 * @method static SessionStoreBase getSessionStore()
 *
 * - From CacheStore
 * @method static void sync(SyncFlag $flag = SyncFlag::Everything)
 * @method static void cache()
 * @method static void clearCache()
 * @method static void reset()
 * @method static Collection getRoles()
 * @method static Collection getPermissions()
 * @method static Collection getMaps()
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
 * - From SessionStore
 * @method static void setAuthUser(Model $user)
 * @method static Model|null getAuthUser()
 * @method static void setAuthUserModel(string $modelClassName)
 * @method static string getAuthUserModel()
 * @method static bool isAuthUser(Model $user, bool $throwException = true)
 * @method static bool isValidUser(Model $user,bool $throwException = true)
 * @method static Collection|null getAssignedRoles()
 * @method static void clearAssignedRoles()
 * @method static void resetAuthUser()
 *
 * @see \Back2Lobby\AccessControl\Services\AccessService;
 * @see \Back2Lobby\AccessControl\Stores\CacheStore;
 */
class AccessControlFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'access-control';
    }
}
