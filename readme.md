<p>
<a href="https://packagist.org/packages/back2lobby/access-control"><img src="https://poser.pugx.org/back2lobby/access-control/d/total.svg" alt="Total Downloads"></a>
<a href="https://github.com/back2lobby/access-control/blob/master/LICENSE.txt"><img src="https://poser.pugx.org/back2lobby/access-control/license.svg" alt="License"></a>
</p>

AccessControl is a Laravel package for easy role & permission management with model-based role assignment and role-based permissions.

## Table of Contents

<details><summary>Click to expand</summary><p>

- [Introduction](#introduction)
- [Installation](#installation)
- [Terminologies](#terminologies)
- [Usage](#usage)
  - [Role](#role)
    - [Creating Role](#creating-role)
    - [Updating Role](#updating-role)
    - [Deleting Role](#deleting-role)
    - [Getting Role](#getting-role)
    - [Allowing Permissions](#allowing-permissions)
    - [Disallowing Permissions](#disallowing-permissions)
    - [Forbidding Permissions](#forbidding-permissions)
    - [Getting Permissions For Role](#getting-permissions-for-role)
    - [Assigning Role](#assigning-role)
    - [Retracting Role](#retracting-role)
    - [Checking Role](#checking-role)
    - [Resetting Role](#resetting-role)
  - [Permission](#permission)
    - [Creating Permission](#creating-permission)
    - [Updating Permission](#updating-permission)
    - [Deleting Permission](#deleting-permission)
    - [Getting Permission](#getting-permission)
    - [Getting Roles Having Permission](#getting-roles-having-permission)
  - [User](#user)
    - [Getting User Roles](#getting-user-roles)
    - [Getting User Permissions](#getting-user-permissions)
    - [Getting Users With Specific Role](#getting-users-with-specific-role)
    - [Getting Users With Specific Permission](#getting-users-with-specific-permission)
    - [Checking User Permission](#checking-user-permission)
    - [Resetting User](#resetting-user)
- [Features](#features)
  - [Cache](#cache)
  </p></details>

## Introduction

AccessControl simplifies role & permission management by enabling the assignment of roles based on models and defining role-based permissions for fine-grained control over user access.

Once installed, you can simply tell the access-control what you want to allow at the gate:

```php
// Give a role some permission
AccessControl::allow("manager")->to('edit-company');

// Assign role to any user
AccessControl::assign('manager')->to($user);

// You can also assign role for a specific roleable model
AccessControl::assign('manager')->to('manager',$company);

// Checking the permission on user for a roleable model
AccessControl::canUser($user)->do("manager",$company);

// Checking if the user has an editor role for that model
AccessControl::is($user)->an("manager",$company);
```

## Installation

> **Note**: AccessControl requires PHP 8.1+ and Laravel 9.0+

1. Install AccessControl with composer:

   ```
   composer require back2lobby/access-control
   ```

2. Add AccessControl's trait to your user model:

   ```php
   use Back2Lobby\AccessControl\Traits\hasRolesAndPermissions;
   class User extends Model
   {
       use hasRolesAndPermissions;
   }
   ```

3. If you have a roleable model, then add AccessControl's trait to your roleable model:

   ```php
   use Back2Lobby\AccessControl\Traits\isRoleable;
   class Post extends Model
   {
       use isRoleable;
   }
   ```

4. Now, to run AccessControl's migrations. First publish the migrations into your app's `migrations` directory, by running the following command:

   ```
   php artisan vendor:publish --tag="access-control.migrations"
   ```

5. Finally, run the migrations:

   ```
   php artisan migrate
   ```

Once it's installed, you can use a seeder to create base roles and permissions for your Laravel application. For example:

```php
use Illuminate\Database\Seeder;
use Back2Lobby\AccessControl\Models\Role;
use Back2Lobby\AccessControl\Models\Permission;

class AccessControlSeeder extends Seeder
{
    public function run()
    {
        // Create all roles
        Role::createManyRoles([
            [
                'name' => 'admin',
                'title' => 'Administrator',
            ],
            [
                'name' => 'editor',
                'title' => 'Editor',
            ]
        ]);

        // Create all permissions
        Permission::createManyPermissions([
            [
                'name' => 'create-post',
                'title' => 'Create Post',
                'description' => 'Allows user to create a new post',
            ],
            [
                'name' => 'edit-post',
                'title' => 'Edit Post',
                'description' => 'Allows user to edit an existing post',
            ],
        ]);
    }
}
```

#### Facade

Whenever you use the `AccessControl` facade in your code, remember to add this line to your namespace imports at the top of the file:

```php
use AccessControl;
```

If your IDE is facing any issues with this facade, please use [barryvdh/laravel-ide-helper](https://github.com/barryvdh/laravel-ide-helper)

## Terminologies

### Role:

A set of permissions that can be assigned to a user.

### Permission:

A right to perform a specific action or access a specific resource.

### Direct permission:

A permission that is allowed or forbidden directly to a role.

### Indirect permission:

A permission that a user has because of a super permission it has, rather than being directly allowed or forbidden.

### Super permission:

A permission that is used to grant all permissions, except for those that are forbidden directly.

### Direct Role

A role is a direct role for a permission if it is allowed for the permission directly.

### Indirect Role

A role is an indirect role for a permission, if it is not allowed for the permission directly rather the role have that permission because it has super permission.

## Usage

### Role

#### Creating Role

Role can be created using <i title="createRole(array $attributes): Role">`createRole`</i> method. Example:

```php
$superAdmin = AccessControl::createRole([
    'name' => 'super-admin',
    'title' => 'Super Admin'
]);
```

You can specify roleables also, which will restrict the role to be assigned for the given roleable. Example:

```php
AccessControl::createRole([
    'name' => 'manager',
    'title' => 'Manager',
    'roleables' => [Company::class]
]);

// &check; assigning role with allowed roleable will work fine
$user->assign('manager',$company);

// &cross; assigning role with roleable that's not allowed will throw error
$user->assign('manager',$post);
```

Multiple roles can be created at once like this:

```php
AccessControl::createManyRole([
    ['name','company-manager','tittle'=>'Company Manager'],
    ['name','company-user','title' => 'Company User']
]);
```

#### Updating Role

Role can be updated using <i title="updateRole(Role|int|string $role, array $attributes): Role">`updateRole`</i> method.
Example:

```php
// using role name
AccessControl::updateRole('author',[
'name' => 'post-author',
'title' => 'Post Author'
]);
```

#### Deleting Role

Role can be deleted using <i title="deleteRole(Role|string|int $role): bool">`deleteRole`</i> method.
Example:

```php
AccessControl::deleteRole('author');
```

#### Getting Role

To retrieve a role, you can use the method <i title="getRole(Role|string|int $role): Role|null">`getRole`</i>. Example:

```php
AccessControl::getRole('admin');
```

To retrieve all the roles available we can do something like to get a Collection of available roles.

```php
AccessControl::getAllRoles();
```

#### Allowing Permissions

To allow a role for a specific permission, you can use the method <i title="allow(Role|string $role): AllowPermission">`allow`</i> and then chain it with method <i title="to(Permission|string $permission): bool">`to`</i>. Example:

```php
AccessControl::allow('author')->to('edit'); // with permission name

AccessControl::allow('author')->to('edit'); // with permission object

AccessControl::allow('author')->to('edit'); // with permission id
```

Alternatively, we can use <i title="allow(Permission $permission): bool">`allow`</i> from Role Model itself like:

```php
$role->allow('create-post');
```

To allow all the permissions available indirectly (except forbidden specifically), use method `superPermission` like this:

```php
AccessControl::allow('super-admin')->superPermission();
```

The method `superPermission` needs the permission named `*`. It will create it in case it's not available.

#### Disallowing Permissions

Take back a permission from a user with method <i title="disallow(Role|string $role): DisallowPermission">`disallow`</i> and chain it with method <i title="to(Permission|string $permission): bool">`to`</i>. Example:

```php
AccessControl::disallow('admin')->to('create admin');
```

Alternatively, we can use <i title="disallow(Permission $permission): bool">`disallow`</i> from Role Model itself like:

```php
$role->disallow('create-post');
```

To take back the super permission given to the role. use method `superPermission()` like this:

```php
AccessControl::disallow('manager')->superPermission();
```

#### Forbidding Permissions

Forbid a permission for the role using method <i title="forbid(Role|string $role): ForbidPermission">`forbid`</i> and chain it with method <i title="to(Permission|string $permission): bool">`to`</i>. Example:

```php
AccessControl::forbid('manager')->to('delete company');
```

Alternatively, we can use <i title="forbid(Permission $permission): bool">`forbid`</i> from Role Model itself like:

```php
$role->forbid('create-post');
```

You can forbid the role from all the permissions indirectly (except allowed specifically) using the method `superPermission` like this:

```php
AccessControl::forbid('manager')->superPermission();
```

#### Getting Permissions For Role

To get all the permissions a role have including allowed and forbidden, we can use method <i title="getAllPermissionsOf(Role $role): Collection">`getAllPermissionsOf`</i> like:

```php
$permissions = AccessControl::getAllPermissionsOf('manager');
```

To get only specific type of permissions for the role, we can use methods <i title="getAllowedPermissionsOf(Role $role): Collection">`getAllowedPermissionsOf`</i>, <i title="getDirectlyAllowedPermissionsOf(Role $role): Collection">`getDirectlyAllowedPermissionsOf`</i>, <i title="getIndirectlyAllowedPermissionsOf(Role $role): Collection">`getIndirectlyAllowedPermissionsOf`</i>, <i title="getForbiddenPermissionsOf(Role $role): Collection">`getForbiddenPermissionsOf`</i>, <i title="getDirectlyForbiddenPermissionsOf(Role $role): Collection">`getDirectlyForbiddenPermissionsOf`</i>, <i title="getIndirectlyForbiddenPermissionsOf(Role $role): Collection">`getIndirectlyForbiddenPermissionsOf`</i>. Examples:

```php
 // getting allowed permissions
 $allowedPermissions = AccessControl::getAllowedPermissionsOf('manager');

 $directlyAllowedPermissions = AccessControl::getDirectlyAllowedPermissionsOf('manager');

 $indirectlyAllowedPermissions = AccessControl::getIndirectlyAllowedPermissionsOf('manager');

 // getting forbidden permissions
 $forbiddenPermissions = AccessControl::getForbiddenPermissionsOf('manager');

 $directlyForbiddenPermissions = AccessControl::getDirectlyForbiddenPermissionsOf('manager');

 $indirectlyForbiddenPermissions = AccessControl::getIndirectlyForbiddenPermissionsOf('manager');
```

Read [Terminologies](#terminologies) if you don't know about direct/indirect permissions.

#### Assigning Role

Role can be assigned to any user using method <i title="assign(Role|string $role, ?Model $roleable = null): AssignRole">`assign`</i>  and chain it with <i title="to(User|int $user): bool">`to`</i> method. Alternatively, you can use the <i title="assign(Role|string $role, Model $roleable = null): bool">`assign`</i> method that's available method on the User model itself. Example:

```php
AccessControl::assign('admin')->to($user); // using facade

$user->assign('editor'); // using user model
```

You can also specify the roleable if the role requires specific roleables like this. For example, you want a user to be manager for a specific project only. It can be achieved like this:

```php
AccessControl::assign('manager',$project)->to($user); // using facade

$user->assign('manager',$project); // using user model
```

#### Retracting Role

Assigned role can be retracted from any user using method <i title="retract(Role|string $role, ?Model $roleable = null): RetractRole">`retract`</i> and chain it with <i title="from(User|int $user): bool">`from`</i> method. Example:

```php
AccessControl::retract('admin')->from($user);
```

You can also specify the roleable to retract role only for the specific roleable. For example, if a user is the manager of multiple companies, the manager role for only given company will be retracted from the user:

```php
AccessControl::retract('manager',$company)->from($user);
```

#### Checking Role

To check if a user have a specific role use method <i title="is(User $user): UserRoleCheck">`is`</i> and chain it with <i title="a(Role|string $role, Model $roleable = null): bool">`a`</i> or <i title="a(Role|string $role, Model $roleable = null): bool">`an`</i> methods. Example:

```php
AccessControl::is($user)->a('manager',$company);

AccessControl::is($user)->an('admin');
```

To check if a user have a specific role chain it with <i title="notA(Role|string $role, Model $roleable = null): bool">`notA`</i> or <i title="notA(Role|string $role, Model $roleable = null): bool">`notAn`</i> methods. Example:

```php
AccessControl::is($user)->notA('manager',$company);

AccessControl::is($user)->notAn('admin');
```

To check if user have all the given roles, we can do something like:

```php
AccessControl::is($user)->all([
    'admin',
    'manager'
]);
```

It doesn't check for any roleables even if the role is restricted to some roleables. For Example, It will return true if the user have manager role for any roleable:

```php
AccessControl::is($user)->all([
    'manager'
]);
```

To check if user have any of the given roles, we can do something like:

```php
AccessControl::is($user)->any([
    'admin',
    'manager'
]);
```

#### Resetting Role

To remove all the permissions from a role, we can reset it using the method <i title="resetRole(Role|string $role): bool">`resetRole`</i> method. Example:

```php
AccessControl::resetRole('admin');
```

### Permission

#### Creating Permission

Permission can be created using the method <i title="createPermission(array $attributes): Permission">`createPermission`</i> method. Example:

```php
AccessControl::createPermission([
    'name' => 'edit-post',
    'title' => 'Edit Post'
]);
```

Multiple permissions can be created at once using the method <i title="createManyPermissions(array $permissions): bool">`createManyPermissions`</i> like this:

```php
AccessControl::createManyPermissions([
    ['name' => 'create-post','title' => 'Create Post'],
    ['name' => 'edit-post','title' => 'Edit Post'],
    ['name' => 'delete-post','title' => 'Delete Post']
]);
```

#### Updating Permission

Permission can be updated using the method <i title="updatePermission(Permission|int|string $permission, array $attributes): Permission">`updatePermission`</i>. For example, to change a permission's name from remove-post to delete-post we can do something like:

```php
AccessControl::updatePermission('remove-post',[
   'name' => 'delete-post',
   'title' => 'Delete Post'
]);
```

#### Deleting Permission

To delete a permission we can use the method <i title="deletePermission(Permission|string|int $permission): bool">`deletePermission`</i> like:

```php
AccessControl::deletePermission('edit-post');
```

#### Getting Permission

To retrieve a permission we can use the method <i title="getPermission(Permission|string|int $permission): Permission|null">`getPermission`</i> like:

```php
AccessControl::getPermission('delete-company');
```

To retrieve all the permissions available, use the method <i title="getPermissions(): Collection">`getPermissions`</i>. Example:
```php
AccessControl::getPermissions();
```

#### Getting Roles Having Permission

To get all the roles that have a permission, we can use method <i title="getAllRolesOf(Permission $permission): Collection">`getAllRolesOf`</i> like:

```php
$rolesWhoCanEdit = AccessControl::getAllRolesOf('edit-post');
```

To get only specific type of roles for the permission, we can use methods <i title="getAllowedRolesOf(Permission $permission): Collection">`getAllowedRolesOf`</i>, <i title="getDirectlyAllowedRolesOf(Permission $permission): Collection">`getDirectlyAllowedRolesOf`</i>, <i title="getIndirectlyAllowedRolesOf(Permission $permission): Collection">`getIndirectlyAllowedRolesOf`</i>, <i title="getForbiddenRolesOf(Permission $permission): Collection">`getForbiddenRolesOf`</i>, <i title="getDirectlyForbiddenRolesOf(Permission $permission): Collection">`getDirectlyForbiddenRolesOf`</i>, <i title="getIndirectlyForbiddenRolesOf(Permission $permission): Collection">`getIndirectlyForbiddenRolesOf`</i>. Examples:

```php
// getting allowed roles
$allowedRoles = AccessControl::getAllowedRolesOf('edit-post');

$directlyAllowedRoles = AccessControl::getDirectlyAllowedRolesOf('edit-post');

$indirectlyAllowedRoles = AccessControl::getIndirectlyAllowedRolesOf('edit-post');

// getting forbidden roles
$forbiddenRoles = AccessControl::getForbiddenRolesOf('edit-post');

$directlyForbiddenRoles = AccessControl::getDirectlyForbiddenRolesOf('edit-post');

$indirectlyForbiddenRoles = AccessControl::getIndirectlyForbiddenRolesOf('edit-post');
```

Read [Terminologies](#terminologies) if you don't know about direct/indirect roles.

### User

#### Getting User Roles

To get all the roles assigned to a user, we can use the method <i title="roles(): BelongsToMany">`roles`</i> provided by `Back2Lobby\AccessControl\Models\User`. Example:

```php
$roles = $user->roles()->get();
```

#### Getting User Permissions

To get all the permissions allowed for user through various roles, we can use the method <i title="permissions(): Collection">`permissions`</i> provided by `Back2Lobby\AccessControl\Models\User`. Example:

```php
$roles = $user->permissions();
```

#### Getting Users With Specific Role

To get all the users that have a specific role, we can use the static method <i title="whereIs(Role|string $role, Model $roleable = null): Builder">`whereIs`</i> provided by `Back2Lobby\AccessControl\Models\User`. Example:

```php
$admins = User::whereIs('admin')->get();
```

If the target role is restricted to some roleables, we can do something like:

```php
$players = User::whereIs('player',$team)->get();
```

You can also reverse the logic by using <i title="users(Model $roleable = null): Builder">`users`</i> method from the role model instead:

```php
$admins = $adminRole->users()->get();
```

#### Getting Users With Specific Permission

To get all the users that have a specific permission, we can use the static method <i title="whereCan(Permission|string $permission, Model $roleable = null, bool $includeIndirectRoles = false): \\Illuminate\Database\Eloquent\Builder">`whereHas`</i> provided by `Back2Lobby\AccessControl\Models\User`. Example:

```php
$users = User::whereHas('edit-post',$post)->get();
```

#### Checking User Permission

To check if a user have specific permission from any role, we can use the method <i title="canUser(User $user): UserPermissionCheck">`canUser`</i> and chain it with method <i title="do(Permission|string $permission, Model $roleable = null): bool">`do`</i> like this:

```php
$canCreatePost = AccessControl::canUser($user)->do('create-post');
```

You can also specify roleables like this:

```php
$canEditPost = AccessControl::canUser($user)->do('edit-post',$post);
```

#### Resetting User

To remove all the roles from a user, we can use the method <i title="resetUser(User $user): bool">`resetUser`</i>. Example:

```php
AccessControl::resetUser($user);
```

## Features

### Cache

All roles and permissions are cached and refreshed automatically every 24 hours. This optimization improves performance and reduces unnecessary database queries. Note that user data is not cached as it can frequently change.

You can manually sync all the roles and permissions with database with <i title="sync(SyncFlag $flag = SyncFlag::SyncAll): void">`sync`</i> method. For example:

```php
AccessControl::sync();
```

To clear the cache you can use the method <i title="clearCache(): void">`clearCache`</i> like:

```php
AccessControl::clearCache();
```

Even after clearing cache the local store will still have the roles and permissions, you can remove them also using the method <i title="reset(): void">`reset`</i>: Example:

```php
AccessControl::reset();
```

Manually caching the store can be achieved using <i title="cache(): void">`cache`</i> like:

```php
AccessControl::cache();
```

### Authorization
To check roles and permissions in blade files, we can use Laravel built in `can` method on the user model. For Example: 
```php
if($user->can('view-dashboard')){
    // your code here
}
```
If you want to check permission for a specific model, then we can do something like:
```php
$user->can('edit-company',$company);
```

### Blade Directive

Similarly, to check roles and permissions in blade files, we can use Laravel built in `@can` directive to check. For Example:

```php
@can('ban-users')
	<button class="btn btn-danger">Ban User</button>
@endcan

@can('create-post',$post)
	<a href="{{ route('post.create') }}">Create Post</a>
@endcan
```

### Middleware
Similarly, built in `can` middleware from Laravel as:
```php
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('can:access-dashboard');
```
This will check if the authenticated user has the access-dashboard permission before processing the request.

You can also use the `can` middleware to check permissions for a specific model instance. For example, the route below will only be processed for users who have the `edit-post` permission on the Post model instance that is passed to route model binding in `/posts/{post}/edit`.
```php
Route::get('/posts/{post}/edit', function () {
    return view('dashboard');
})->middleware('can:edit-post,' . Post::class);
```
In this case, `Post::class` is passed to specify the model class for which the permission check should be performed. Note that this will only work if the route has route model binding for the Post model.

