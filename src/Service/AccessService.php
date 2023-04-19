<?php

namespace Back2Lobby\AccessControl\Service;

use Back2Lobby\AccessControl\Exceptions\InvalidAttributesException;
use Back2Lobby\AccessControl\Exceptions\InvalidPermissionException;
use Back2Lobby\AccessControl\Exceptions\InvalidRoleException;
use Back2Lobby\AccessControl\Exceptions\InvalidUserException;
use Back2Lobby\AccessControl\Exceptions\UpdateProcessFailedException;
use Back2Lobby\AccessControl\Models\Permission;
use Back2Lobby\AccessControl\Models\Role;
use Back2Lobby\AccessControl\Models\User;
use Back2Lobby\AccessControl\Service\Contracts\Accessable;
use Back2Lobby\AccessControl\Store\Abstracts\Storable;
use Back2Lobby\AccessControl\Store\Enumerations\SyncFlag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AccessService implements Accessable
{
    private static Storable $store;

    public function __construct(Storable $store)
    {
        self::$store = $store;
    }

    public function getStore(): Storable
    {
        return self::$store;
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

        return Role::create([
            'name' => $attributes['name'],
            'title' => $attributes['title'],
            'roleables' => isset($attributes['roleables']) && ! empty($attributes['roleables']) ? $attributes['roleables'] : null,
        ]);
    }

    public function createManyRoles(array $roles): bool
    {
        $validator = Validator::make(['roles' => $roles], [
            'roles' => 'array|min:1',
            'roles.*.name' => ['required', 'string', 'unique:roles,name', function ($attribute, $value, $fail) use ($roles) {
                //make sure name is unique among other names in roles array
                if (count(array_filter($roles, fn ($r) => isset($r['name']) && $r['name'] === $value)) !== 1) {
                 $fail('Duplicate roles passed. Please make sure role name is unique.');
                }
            }],
            'roles.*.title' => 'required|string',
            'roles.*.roleables' => 'array|nullable',
            'roles.*.roleables.*' => 'string|distinct',
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

        $this->getStore()->sync(SyncFlag::OnlyRole);

        return $result;
    }

    public function updateRole(Role $role, array $attributes): Role
    {
        if ($role = $this->getStore()->getRole($role)) {
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

            if ($result && $role = $this->getStore()->getRole($role)) {
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

        return Permission::create([
            'name' => $attributes['name'],
            'title' => $attributes['title'],
            'description' => $attributes['description'] ?? null,
        ]);
    }

    public function createManyPermissions(array $permissions): bool
    {
        $validator = Validator::make(['permissions' => $permissions], [
            'permissions' => 'array|min:1',
            'permissions.*.name' => ['required', 'string', 'unique:permissions,name', function ($attribute, $value, $fail) use ($permissions) {
                //make sure name is unique among other names in permissions array
                if (count(array_filter($permissions, fn ($p) => isset($p['name']) && $p['name'] === $value)) !== 1) {
                    $fail('Duplicate permissions passed. Please make sure permission name is unique.');
                }
            }],
            'permissions.*.title' => 'required|string',
            'permissions.*.description' => 'string|nullable',
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

        $this->getStore()->sync(SyncFlag::OnlyPermission);

        return $result;
    }

    public function updatePermission(Permission $permission, array $attributes): Permission
    {
        if ($permission = $this->getStore()->getPermission($permission)) {
            $validator = Validator::make($attributes, [
                'name' => 'string|unique:permissions,name',
                'title' => 'string',
                'description' => 'string|nullable',
            ]);

            if ($validator->stopOnFirstFailure()->fails()) {
                throw new InvalidAttributesException(collect($validator->errors()->getMessages())->flatten()->first());
            }

            $result = $permission->update($validator->safe()->toArray());

            if ($result && $permission = $this->getStore()->getPermission($permission)) {
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
        if ($role = $this->getStore()->getRole($role)) {
            $deleted = $role->delete() === true;

            // roles will auto sync on deletion, but we have to update maps manually
            $this->getStore()->sync(SyncFlag::OnlyMap);
        }

        return $deleted ?? false;
    }

    public function deletePermission(Permission|string|int $permission): bool
    {
        if ($permission = $this->getStore()->getPermission($permission)) {
            $deleted = $permission->delete() === true;

            // permission will auto sync on deletion, but we have to update maps manually
            $this->getStore()->sync(SyncFlag::OnlyMap);
        }

        return $deleted ?? false;
    }

    public function allow(Role|string $role): AllowPermission
    {
        if ($role = $this->getStore()->getRole($role)) {
            return new AllowPermission($this->getStore(), $role);
        } else {
            throw new InvalidRoleException("Provided role $role cannot be validated because its either invalid or not found in database");
        }
    }

    public function disallow(Role|string $role): DisallowPermission
    {
        if ($role = $this->getStore()->getRole($role)) {
            return new DisallowPermission($this->getStore(), $role);
        } else {
            throw new InvalidRoleException('Provided role cannot be validated because its either invalid or not found in database');
        }
    }

    public function forbid(Role|string $role): ForbidPermission
    {
        if ($role = $this->getStore()->getRole($role)) {
            return new ForbidPermission($this->getStore(), $role);
        } else {
            throw new InvalidRoleException('Provided role cannot be validated because its either invalid or not found in database');
        }
    }

    public function assign(Role|string $role, ?Model $roleable = null): AssignRole
    {
        if ($role = $this->getStore()->getRole($role)) {
            return new AssignRole($role, $roleable);
        } else {
            throw new InvalidRoleException('Provided role cannot be validated because its either invalid or not found in database');
        }
    }

    public function retract(Role|string $role, ?Model $roleable = null): RetractRole
    {
        if ($role = $this->getStore()->getRole($role)) {
            return new RetractRole($role, $roleable);
        } else {
            throw new InvalidRoleException('Provided role cannot be validated because its either invalid or not found in database');
        }
    }

    public function is(User $user): UserRoleCheck
    {
        if ($user->id) {
            return new UserRoleCheck($this->getStore(), $user);
        } else {
            throw new InvalidUserException('Provided user does not have a valid `id` attribute');
        }
    }

    public function canRole(Role|string $role): RolePermissionCheck
    {
        if ($role = $this->getStore()->getRole($role)) {
            return new RolePermissionCheck($this->getStore(), $role);
        } else {
            throw new InvalidRoleException('Provided role cannot be validated because its either invalid or not found in database');
        }
    }

    public function canUser(User $user): UserPermissionCheck
    {
        if ($user->id) {
            return new UserPermissionCheck($this->getStore(), $user);
        } else {
            throw new InvalidUserException('Provided user does not have a valid `id` attribute');
        }
    }

    public function resetRole(Role|string $role): bool
    {
        if ($role = $this->getStore()->getRole($role)) {
            $rowsUpdated = DB::table('permission_role')->where('role_id', $role->id)->delete() >= 0;

            if ($rowsUpdated) {
                $this->getStore()->sync(SyncFlag::OnlyMap);
            }

            return $rowsUpdated;
        }

        return false;
    }

    public function resetUser(User $user): bool
    {
        if ($user->id) {
            return DB::table('role_user')->where('user_id', $user->id)->delete() >= 0;
        }

        return false;
    }
}
