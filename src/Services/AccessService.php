<?php

namespace Back2Lobby\AccessControl\Services;

use Back2Lobby\AccessControl\Exceptions\InvalidAttributesException;
use Back2Lobby\AccessControl\Exceptions\InvalidPermissionException;
use Back2Lobby\AccessControl\Exceptions\InvalidRoleException;
use Back2Lobby\AccessControl\Exceptions\InvalidUserException;
use Back2Lobby\AccessControl\Exceptions\UpdateProcessFailedException;
use Back2Lobby\AccessControl\Models\Permission;
use Back2Lobby\AccessControl\Models\Role;
use Back2Lobby\AccessControl\Services\Contracts\Accessable;
use Back2Lobby\AccessControl\Stores\Abstracts\CacheStoreBase;
use Back2Lobby\AccessControl\Stores\Abstracts\SessionStoreBase;
use Back2Lobby\AccessControl\Stores\Enumerations\SyncFlag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AccessService implements Accessable
{
    private static CacheStoreBase $cacheStore;

    private static SessionStoreBase $sessionStore;

    public function __construct(CacheStoreBase $cacheStore, SessionStoreBase $sessionStore)
    {
        self::$cacheStore = $cacheStore;

        self::$sessionStore = $sessionStore;
    }

    public function getCacheStore(): CacheStoreBase
    {
        return self::$cacheStore;
    }

    public function getSessionStore(): SessionStoreBase
    {
        return self::$sessionStore;
    }

    public function createRole(array $attributes): Role
    {
        $validator = Validator::make($attributes, [
            'name' => 'required|string|unique:roles,name',
            'title' => 'required|string',
            'roleables' => 'array|nullable',
            'roleables.*' => 'string|distinct',
        ]);

        if ($validator->stopOnFirstFailure()->fails()) {
            throw new InvalidAttributesException(collect($validator->errors()->getMessages())->flatten()->first());
        }

        $role = Role::create([
            'name' => $attributes['name'],
            'title' => $attributes['title'],
            'roleables' => isset($attributes['roleables']) && ! empty($attributes['roleables']) ? $attributes['roleables'] : null,
        ]);

        $this->getCacheStore()->sync(SyncFlag::OnlyRoles);

        return $role;
    }

    public function createManyRoles(array $roles): bool
    {
        $validator = Validator::make(['roles' => $roles], [
            'roles' => 'present|array|min:1',
            'roles.*' => 'array',
            'roles.*.name' => ['required', 'string', 'unique:roles,name', function ($attribute, $value, $fail) use ($roles) {
                //make sure name is unique among other names in roles array
                if (count(array_filter($roles, fn ($r) => isset($r['name']) && $r['name'] === $value)) !== 1) {
                    $fail('Duplicate roles passed. Please make sure role name is unique.');
                }
            }],
            'roles.*.title' => 'required|string',
            'roles.*.roleables' => 'array|nullable',
            'roles.*.roleables.*' => ['string', function ($attribute, $value, $fail) use ($roles) {
                [, $rollNumber] = explode('.', $attribute);
                if (is_numeric($rollNumber)) {
                    $roleables = $roles[intval($rollNumber)]['roleables'];

                    if (count($roleables) !== count(array_flip($roleables))) {
                        $fail('Role #'.$rollNumber.' has duplicate roleables.');
                    }
                } else {
                    $fail('One of the roleables is not valid.');
                }
            }],
        ], [], [
            'roles.*.name' => 'role #:position name',
            'roles.*.title' => 'role #:position title',
            'roles.*.roleables' => 'role #:position roleables',
        ]);

        if ($validator->stopOnFirstFailure()->fails()) {
            throw new InvalidAttributesException(collect($validator->errors()->getMessages())->flatten()->first());
        }

        // cast the roleables to json and set it to null if it isn't available
        $roles = array_map(function ($role) {
            if (isset($role['roleables']) && is_array($role['roleables'])) {
                $role['roleables'] = json_encode($role['roleables']);
            } else {
                $role['roleables'] = null;
            }

            return $role;
        }, $roles);

        $result = Role::insert($roles);

        $this->getCacheStore()->sync(SyncFlag::OnlyRoles);

        return $result;
    }

    public function updateRole(Role|int|string $role, array $attributes): Role
    {
        if ($role = $this->getCacheStore()->getRole($role)) {
            $validator = Validator::make($attributes, [
                'name' => 'string|unique:roles,name',
                'title' => 'string',
                'roleables' => 'array|nullable',
                'roleables.*' => 'string|distinct',
            ]);

            if ($validator->stopOnFirstFailure()->fails()) {
                throw new InvalidAttributesException(collect($validator->errors()->getMessages())->flatten()->first());
            }

            $result = $role->update($validator->safe()->toArray());

            if ($result && $role = $this->getCacheStore()->getRole($role)) {

                $this->getCacheStore()->sync(SyncFlag::OnlyRoles);

                return $role;
            } else {
                throw new UpdateProcessFailedException('Role could not be updated');
            }
        } else {
            throw new InvalidRoleException("Provided role couldn't be found");
        }
    }

    public function createPermission(array $attributes): Permission
    {
        $validator = Validator::make($attributes, [
            'name' => 'required|string|unique:permissions,name',
            'title' => 'required|string',
            'description' => 'string|nullable',
        ]);

        if ($validator->stopOnFirstFailure()->fails()) {
            throw new InvalidAttributesException(collect($validator->errors()->getMessages())->flatten()->first());
        }

        $permission = Permission::create([
            'name' => $attributes['name'],
            'title' => $attributes['title'],
            'description' => $attributes['description'] ?? null,
        ]);

        $this->getCacheStore()->sync(SyncFlag::OnlyPermissions);

        return $permission;
    }

    public function createManyPermissions(array $permissions): bool
    {
        $validator = Validator::make(['permissions' => $permissions], [
            'permissions' => 'present|array|min:1',
            'permissions.*' => 'array',
            'permissions.*.name' => ['required', 'string', 'unique:permissions,name', function ($attribute, $value, $fail) use ($permissions) {
                //make sure name is unique among other names in permissions array
                if (count(array_filter($permissions, fn ($p) => isset($p['name']) && $p['name'] === $value)) !== 1) {
                    $fail('Duplicate permissions passed. Please make sure permission name is unique.');
                }
            }],
            'permissions.*.title' => 'required|string',
            'permissions.*.description' => 'string|nullable',
        ], [], [
            'permissions.*.name' => 'permission #:position name',
            'permissions.*.title' => 'permission #:position title',
            'permissions.*.description' => 'permission #:position description',
        ]);

        if ($validator->stopOnFirstFailure()->fails()) {
            throw new InvalidAttributesException(collect($validator->errors()->getMessages())->flatten()->first());
        }

        // set description to null if not available because all the rows passed to insert() must have same length
        $permissions = array_map(function ($permission) {
            if (! (isset($permission['description']) && is_string($permission['description']))) {
                $permission['description'] = null;
            }

            return $permission;
        }, $permissions);

        $result = Permission::insert($permissions);

        $this->getCacheStore()->sync(SyncFlag::OnlyPermissions);

        return $result;
    }

    public function updatePermission(Permission|int|string $permission, array $attributes): Permission
    {
        if ($permission = $this->getCacheStore()->getPermission($permission)) {
            $validator = Validator::make($attributes, [
                'name' => 'string|unique:permissions,name',
                'title' => 'string',
                'description' => 'string|nullable',
            ]);

            if ($validator->stopOnFirstFailure()->fails()) {
                throw new InvalidAttributesException(collect($validator->errors()->getMessages())->flatten()->first());
            }

            $result = $permission->update($validator->safe()->toArray());

            if ($result && $permission = $this->getCacheStore()->getPermission($permission)) {

                $this->getCacheStore()->sync(SyncFlag::OnlyPermissions);

                return $permission;
            } else {
                throw new UpdateProcessFailedException('Permission could not be updated');
            }
        } else {
            throw new InvalidPermissionException("Provided permission couldn't be found");
        }
    }

    public function deleteRole(Role|string|int $role): bool
    {
        if ($role = $this->getCacheStore()->getRole($role)) {
            $deleted = $role->delete() === true;

            // we have to update roles and maps manually
            $this->getCacheStore()->sync(SyncFlag::OnlyRoles);
            $this->getCacheStore()->sync(SyncFlag::OnlyMaps);

            // reset the loaded roles for current user
            $this->getSessionStore()->clearAssignedRoles();
        }

        return $deleted ?? false;
    }

    public function deletePermission(Permission|string|int $permission): bool
    {
        if ($permission = $this->getCacheStore()->getPermission($permission)) {
            $deleted = $permission->delete() === true;

            // permission will auto sync on deletion, but we have to update maps manually
            $this->getCacheStore()->sync(SyncFlag::OnlyPermissions);
            $this->getCacheStore()->sync(SyncFlag::OnlyMaps);
        }

        return $deleted ?? false;
    }

    public function allow(Role|string $role): AllowPermission
    {
        if ($role = $this->getCacheStore()->getRole($role)) {
            return new AllowPermission($this->getCacheStore(), $role);
        } else {
            throw new InvalidRoleException("Provided role $role cannot be validated because its either invalid or not found in database");
        }
    }

    public function disallow(Role|string $role): DisallowPermission
    {
        if ($role = $this->getCacheStore()->getRole($role)) {
            return new DisallowPermission($this->getCacheStore(), $role);
        } else {
            throw new InvalidRoleException('Provided role cannot be validated because its either invalid or not found in database');
        }
    }

    public function forbid(Role|string $role): ForbidPermission
    {
        if ($role = $this->getCacheStore()->getRole($role)) {
            return new ForbidPermission($this->getCacheStore(), $role);
        } else {
            throw new InvalidRoleException('Provided role cannot be validated because its either invalid or not found in database');
        }
    }

    public function assign(Role|string $role, ?Model $roleable = null): AssignRole
    {
        if ($role = $this->getCacheStore()->getRole($role)) {
            return new AssignRole($role, $roleable);
        } else {
            throw new InvalidRoleException('Provided role cannot be validated because its either invalid or not found in database');
        }
    }

    public function retract(Role|string $role, ?Model $roleable = null): RetractRole
    {
        if ($role = $this->getCacheStore()->getRole($role)) {
            return new RetractRole($role, $roleable);
        } else {
            throw new InvalidRoleException('Provided role cannot be validated because its either invalid or not found in database');
        }
    }

    public function is(Model $user): UserRoleCheck
    {
        if ($this->getSessionStore()->isValidUser($user)) {
            return new UserRoleCheck($this->getCacheStore(), $user);
        } else {
            throw new InvalidUserException('Provided user does not have a valid `id` attribute');
        }
    }

    public function canRole(Role|string $role): RolePermissionCheck
    {
        if ($role = $this->getCacheStore()->getRole($role)) {
            return new RolePermissionCheck($this->getCacheStore(), $role);
        } else {
            throw new InvalidRoleException('Provided role cannot be validated because its either invalid or not found in database');
        }
    }

    public function canUser(Model $user): UserPermissionCheck
    {
        if ($this->getSessionStore()->isValidUser($user)) {
            return new UserPermissionCheck($this->getCacheStore(), $user);
        } else {
            throw new InvalidUserException('Provided user does not have a valid `id` attribute');
        }
    }

    public function resetRole(Role|string $role): bool
    {
        if ($role = $this->getCacheStore()->getRole($role)) {
            $rowsUpdated = DB::table('assigned_permissions')->where('role_id', $role->id)->delete() >= 0;

            if ($rowsUpdated) {
                $this->getCacheStore()->sync(SyncFlag::OnlyRoles);
                $this->getCacheStore()->sync(SyncFlag::OnlyMaps);
            }

            return $rowsUpdated;
        }

        return false;
    }

    public function resetUser(Model $user): bool
    {
        $result = false;

        if ($this->getSessionStore()->isValidUser($user)) {

            $userColumnName = Str::singular($this->getSessionStore()->getAuthUserTable()).'_id';

            $result = DB::table('assigned_roles')->where($userColumnName, $user->id)->delete() >= 0;

            // reset the loaded user roles
            $this->getSessionStore()->clearAssignedRoles();
        }

        return $result;
    }
}
