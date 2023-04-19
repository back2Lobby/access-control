<?php

namespace Back2Lobby\AccessControl\Tests;

use Back2Lobby\AccessControl\Exceptions\InvalidAttributesException;
use Back2Lobby\AccessControl\Exceptions\InvalidPermissionException;
use Back2Lobby\AccessControl\Exceptions\InvalidRoleException;
use Back2Lobby\AccessControl\Exceptions\InvalidUserException;
use Back2Lobby\AccessControl\Exceptions\UpdateProcessFailedException;
use Back2Lobby\AccessControl\Models\Permission;
use Back2Lobby\AccessControl\Models\Role;
use Back2Lobby\AccessControl\Models\User;
use Back2Lobby\AccessControl\Service\AccessService;
use Back2Lobby\AccessControl\Service\AllowPermission;
use Back2Lobby\AccessControl\Service\AssignRole;
use Back2Lobby\AccessControl\Service\Contracts\Accessable;
use Back2Lobby\AccessControl\Service\DisallowPermission;
use Back2Lobby\AccessControl\Service\ForbidPermission;
use Back2Lobby\AccessControl\Service\RetractRole;
use Back2Lobby\AccessControl\Service\RolePermissionCheck;
use Back2Lobby\AccessControl\Service\UserPermissionCheck;
use Back2Lobby\AccessControl\Service\UserRoleCheck;
use Back2Lobby\AccessControl\Store\Abstracts\Storable;
use Back2Lobby\AccessControl\Store\StoreService;
use Back2Lobby\AccessControl\Tests\Models\Company;
use Back2Lobby\AccessControl\Tests\Models\Post;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ValidatedInput;

/**
 * @method Accessable getAccessService()
 *
 * @coversDefaultClass \Back2Lobby\AccessControl\Service\AccessService
 */
class AccessServiceTest extends BaseTestCase
{
    /*
     * Using this syntax/way to make a new `AccessService` instance available for all the test
     * methods because:
     * - setUp method is called even before the db connection for some reason so sync method on store object gives error
     * - it's not good to extend the `PHPUnit\Framework\TestCase` constructor
     * */
    public function __call($method_name, $arguments)
    {
        if ($method_name === 'getAccessService') {
            return new AccessService(StoreService::getInstance());
        } else {
            // Call the parent method for all other magic methods
            return parent::$method_name($arguments);
        }
    }

    /**
     * @covers ::getStore
     *
     * @test
     */
    public function it_returns_valid_storable_instance()
    {
        $this->assertInstanceOf(Storable::class, $this->getAccessService()->getStore());
    }

    /**
     * @covers ::createRole
     *
     * @test
     */
    public function it_allows_creating_a_role()
    {
        $role1 = $this->getAccessService()->createRole([
            'name' => 'admin',
            'title' => 'Admin',
        ]);

        $role2 = $this->getAccessService()->createRole([
            'name' => 'editor',
            'title' => 'Editor',
            'roleables' => [
                Post::class,
            ],
        ]);

        $this->assertInstanceOf(Role::class, $role1);
        $this->assertInstanceOf(Role::class, $role2);

        $this->assertCount(2, $this->getAccessService()->getStore()->getRoles());

        $this->assertCount(1, $this->getAccessService()->getStore()->getRoles()->where('id', $role1->id));
        $this->assertCount(1, $this->getAccessService()->getStore()->getRoles()->where('name', $role1->name));

        $this->assertCount(1, $this->getAccessService()->getStore()->getRoles()->where('id', $role2->id));
        $this->assertCount(1, $this->getAccessService()->getStore()->getRoles()->where('name', $role2->name));
    }

    /**
     * @covers ::createRole
     *
     * @test
     */
    public function it_syncs_the_role_after_creating()
    {
        $this->getAccessService()->createRole([
            'name' => 'admin',
            'title' => 'Admin',
        ]);

        $this->assertCount(1, $this->getAccessService()->getStore()->getRoles());
        $this->assertCount(1, $this->getAccessService()->getStore()->getRoles()->where('name', 'admin'));
    }

    /**
     * @covers ::createRole
     *
     * @dataProvider createRoleProvider
     *
     * @test
     */
    public function it_throws_exception_when_invalid_attributes_are_provided_to_create_a_role(array $role, string $expectedErrorMessage = null, \Closure $init = null)
    {
        if ($init) {
        $init();
        }
        $this->assertException(InvalidAttributesException::class, function () use ($role) {
            $this->getAccessService()->createRole($role);
        }, $expectedErrorMessage);
    }

    public function createRoleProvider(): array
    {
        return [
            'Required Name' => [
                ['title' => 'Admin'],
                'The name field is required.',
            ],
            'Required Title' => [
                ['name' => 'admin'],
                'The title field is required.',
            ],
            'Only String Name' => [
                ['name' => 452, 'title' => 'Admin'],
                'The name field must be a string.',
            ],
            'Only String Title' => [
                ['name' => 'admin', 'title' => 456],
                'The title field must be a string.',
            ],
            'Unique Name' => [
                ['name' => 'admin', 'title' => 'New Admin'],
                'The name has already been taken.',
                fn () => $this->getAccessService()->createRole(['name' => 'admin', 'title' => 'Admin']),
            ],
            'Non Array Roleables' => [
                ['name' => 'editor', 'title' => 'Editor', 'roleables' => 34],
                'The roleables field must be an array.',
            ],
            'Non-String Elements In Roleables Array' => [
                ['name' => 'editor', 'title' => 'Editor', 'roleables' => [34, 'test']],
                'The roleables.0 field must be a string.',
            ],
            'Duplicates in Roleable Array' => [
                ['name' => 'editor', 'title' => 'Editor', 'roleables' => [Post::class, Post::class]],
                'The roleables.0 field has a duplicate value.',
            ],
        ];
    }

    /**
     * @covers ::createManyRoles
     *
     * @test
     */
    public function it_allows_creating_many_roles_at_once()
    {
        $this->getAccessService()->createManyRoles([
            ['name' => 'admin', 'title' => 'Admin'],
            ['name' => 'manager', 'title' => 'Manager'],
            ['name' => 'editor', 'title' => 'Editor', 'roleables' => [Post::class]],
        ]);

        $this->assertCount(3, $this->getAccessService()->getStore()->getRoles());

        $this->assertCount(1, $this->getAccessService()->getStore()->getRoles()->where('name', 'admin'));
        $this->assertCount(1, $this->getAccessService()->getStore()->getRoles()->where('name', 'manager'));
        $this->assertCount(1, $this->getAccessService()->getStore()->getRoles()->where('name', 'editor'));
        $this->assertSameArray([Post::class], $this->getAccessService()->getStore()->getRoles()->where('name', 'editor')->first()->roleables);
    }

    /**
     * @covers ::createManyRoles
     *
     * @test
     */
    public function it_syncs_roles_after_creating_many_roles_at_once()
    {
        $this->getAccessService()->createManyRoles([
            ['name' => 'admin', 'title' => 'Admin'],
            ['name' => 'editor', 'title' => 'Editor', 'roleables' => [Post::class]],
        ]);

        $this->assertCount(2, $this->getAccessService()->getStore()->getRoles());
    }

    /**
     * @covers ::createManyRoles
     *
     * @dataProvider createManyRolesProvider
     *
     * @test
     */
    public function it_throws_exception_when_invalid_attributes_are_passed_while_creating_many_roles_at_once(array $roles, string $expectedErrorMessage = null, \Closure $init = null)
    {
        if ($init) {
        $init();
        }
        $this->assertException(InvalidAttributesException::class, function () use ($roles) {
            $this->getAccessService()->createManyRoles($roles);
        }, $expectedErrorMessage);
    }

    public function createManyRolesProvider(): array
    {
        return [
            'Empty Roles Array' => [
                [],
                'The roles field must have at least 1 items.',
            ],
            'Required Role Name' => [
                [
                    ['name' => 'admin', 'title' => 'admin'],
                    ['title' => 'Super Admin'],
                ],
                'The roles.1.name field is required.',
            ],
            'Only String Role Name' => [
                [
                    ['name' => 'admin', 'title' => 'admin'],
                    ['name' => 34, 'title' => 'Super Admin'],
                ],
                'The roles.1.name field must be a string.',
            ],
            'Only String Role Title' => [
                [
                    ['name' => 'admin', 'title' => 'admin'],
                    ['name' => 'super admin', 'title' => 829],
                ],
                'The roles.1.title field must be a string.',
            ],
            'Unique Role Name' => [
                [
                    ['name' => 'admin', 'title' => 'Admin'],
                    ['name' => 'editor', 'title' => 'New Editor'],
                ],
                'The roles.1.name has already been taken.',
                fn () => $this->getAccessService()->createRole(['name' => 'editor', 'title' => 'Editor']),
            ],
            'Duplicate Role Name' => [
                [
                    ['name' => 'admin', 'title' => 'Admin'],
                    ['name' => 'manager', 'title' => 'manager'],
                    ['name' => 'manager', 'title' => 'New Manager'],
                ],
                'Duplicate roles passed. Please make sure role name is unique.',
            ],
            'Non Array Roleables' => [
                [
                    ['name' => 'editor', 'title' => 'Editor', 'roleables' => 34],
                ],
                'The roles.0.roleables field must be an array.',
            ],
            'Non-String Elements In Roleables Array' => [
                [
                    ['name' => 'editor', 'title' => 'Editor', 'roleables' => [34, 'test']],
                ],
                'The roles.0.roleables.0 field must be a string.',
            ],
            'Duplicates in Roleable Array' => [
                [
                    ['name' => 'editor', 'title' => 'Editor', 'roleables' => [Post::class, Post::class]],
                ],
                'The roles.0.roleables.0 field has a duplicate value.',
            ],
        ];
    }

    /**
     * @covers ::updateRole
     *
     * @test
     */
    public function it_allows_updating_role()
    {
        $role = $this->getAccessService()->createRole([
            'name' => 'admin',
            'title' => 'Admin',
        ]);

        $this->getAccessService()->updateRole($role, [
            'title' => 'New Title',
        ]);

        $this->assertNotNull($this->getAccessService()->getStore()->getRole($role->id));
        $this->assertSame('New Title', $this->getAccessService()->getStore()->getRole($role->id)->title);
    }

    /**
     * @covers ::updateRole
     *
     * @test
     */
    public function it_syncs_role_after_update()
    {
        $role1 = $this->getAccessService()->createRole([
            'name' => 'admin',
            'title' => 'Admin',
        ]);

        $role2 = $this->getAccessService()->createRole([
            'name' => 'manager',
            'title' => 'Manager',
        ]);

        $this->getAccessService()->updateRole($role1, [
            'title' => 'New Title',
        ]);

        $this->getAccessService()->updateRole($role2, [
            'name' => 'New Name',
        ]);

        $this->assertCount(2, $this->getAccessService()->getStore()->getRoles());
        $this->assertNotNull($this->getAccessService()->getStore()->getRole($role1->id));
        $this->assertSame('New Title', $this->getAccessService()->getStore()->getRole($role1->id)->title);
        $this->assertNotNull($this->getAccessService()->getStore()->getRole($role2->id));
        $this->assertSame('New Name', $this->getAccessService()->getStore()->getRole($role2->id)->name);
    }

    /**
     * @covers ::updateRole
     *
     * @dataProvider updateRoleProvider
     *
     * @test
     */
    public function it_throws_exception_when_invalid_attributes_are_provided_to_update_a_role(array $attributes, string $expectedErrorMessage = null)
    {
        $role = $this->getAccessService()->createRole([
            'name' => 'admin',
            'title' => 'Admin',
        ]);

        $this->assertException(InvalidAttributesException::class, function () use ($role, $attributes) {
            $this->getAccessService()->updateRole($role, $attributes);
        }, $expectedErrorMessage);
    }

    public function updateRoleProvider(): array
    {
        return [
            'Only String Name' => [
                ['name' => 345],
                'The name field must be a string.',
            ],
            'Only String Title' => [
                ['title' => 567],
                'The title field must be a string.',
            ],
            'Unique Name' => [
                ['name' => 'admin', 'title' => 'New Admin'],
                'The name has already been taken.',
                fn () => $this->getAccessService()->createRole(['name' => 'admin', 'title' => 'Admin']),
            ],
            'Non Array Roleables' => [
                ['roleables' => 34],
                'The roleables field must be an array.',
            ],
            'Non-String Elements In Roleables Array' => [
                ['roleables' => [34, 'test']],
                'The roleables.0 field must be a string.',
            ],
            'Duplicates in Roleable Array' => [
                ['roleables' => [Post::class, Post::class]],
                'The roleables.0 field has a duplicate value.',
            ],
        ];
    }

    /**
     * @covers ::updateRole
     *
     * @test
     */
    public function it_throws_exception_if_passed_role_is_not_valid_while_updating_role()
    {
        $role = Role::factory()->make();

        $this->expectException(InvalidRoleException::class);
        $this->getAccessService()->updateRole($role, [
            'name' => 'Admin',
        ]);
    }

    /**
     * @covers ::updateRole
     *
     * @test
     */
    public function it_throws_exception_if_role_could_not_be_updated_even_after_passing_the_validation()
    {
        $role = $this->getAccessService()->createRole([
            'name' => 'admin',
            'title' => 'Admin',
        ]);

        $validatorMock = \Mockery::mock();
        $validatorMock->shouldReceive('fails')->once()->andReturn(false);
        $validatorMock->shouldReceive('stopOnFirstFailure')->once()->andReturn(\Mockery::self());

        $validatorMock->shouldReceive('safe')->once()->andReturnUsing(function () use ($role) {
            $this->getAccessService()->deleteRole($role);

            return new ValidatedInput([
                'name' => 'Admin',
            ]);
        });

        Validator::shouldReceive('make')->once()->andReturn($validatorMock);

        $this->expectException(UpdateProcessFailedException::class);
        $this->getAccessService()->updateRole($role, [
            'name' => 'Admin',
        ]);
    }

    /**
     * @covers ::createPermission
     *
     * @test
     */
    public function it_allows_creating_a_permission()
    {
        $this->getAccessService()->createPermission([
            'name' => 'view-post',
            'title' => 'View Post',
        ]);

        $this->getAccessService()->createPermission([
            'name' => 'edit-post',
            'title' => 'Edit Post',
            'description' => 'Allow to Edit the posts',
        ]);

        $this->assertCount(2, $this->getAccessService()->getStore()->getPermissions());
        $this->assertCount(1, $this->getAccessService()->getStore()->getPermissions()->where('name', 'view-post'));
        $this->assertCount(1, $this->getAccessService()->getStore()->getPermissions()->where('name', 'edit-post'));
    }

    /**
     * @covers ::createPermission
     *
     * @test
     */
    public function it_syncs_the_permission_after_creating()
    {
        $this->getAccessService()->createPermission([
            'name' => 'edit-posts',
            'title' => 'Edit Posts',
        ]);

        $this->assertCount(1, $this->getAccessService()->getStore()->getPermissions());
        $this->assertCount(1, $this->getAccessService()->getStore()->getPermissions()->where('name', 'edit-posts'));
    }

    /**
     * @covers ::createPermission
     *
     * @dataProvider createPermissionProvider
     *
     * @test
     */
    public function it_throws_exception_when_invalid_attributes_are_provided_to_create_a_permission(array $permission, string $expectedErrorMessage = null, \Closure $init = null)
    {
        if ($init) {
        $init();
        }

        $this->assertException(InvalidAttributesException::class, function () use ($permission) {
            $this->getAccessService()->createPermission($permission);
        }, $expectedErrorMessage);
    }

    public function createPermissionProvider(): array
    {
        return [
            'Required Name' => [
                ['title' => 'Edit Post'],
                'The name field is required.',
            ],
            'Required Title' => [
                ['name' => 'edit-post'],
                'The title field is required.',
            ],
            'Only String Name' => [
                ['name' => 467, 'title' => 'Edit Post'],
                'The name field must be a string.',
            ],
            'Only String Title' => [
                ['name' => 'edit-post', 'title' => 236],
                'The title field must be a string.',
            ],
            'Unique Name' => [
                ['name' => 'edit-post', 'title' => 'New Edit Post'],
                'The name has already been taken.',
                fn () => Permission::factory()->createFake(['name' => 'edit-post']),
            ],
            'Only String Description' => [
                ['name' => 'edit-post', 'title' => 'Edit Post', 'description' => 6346],
                'The description field must be a string.',
            ],
        ];
    }

    /**
     * @covers ::createManyPermissions
     *
     * @test
     */
    public function it_allows_creating_many_permissions_at_once()
    {
        $this->getAccessService()->createManyPermissions([
            ['name' => 'edit-post', 'title' => 'Edit Post'],
            ['name' => 'view-post', 'title' => 'View Post'],
            ['name' => 'delete-post', 'title' => 'Delete Post', 'description' => 'delete the post permanently'],
        ]);

        $this->assertCount(3, $this->getAccessService()->getStore()->getPermissions());

        $this->assertCount(1, $this->getAccessService()->getStore()->getPermissions()->where('name', 'edit-post'));
        $this->assertCount(1, $this->getAccessService()->getStore()->getPermissions()->where('name', 'view-post'));
        $this->assertCount(1, $this->getAccessService()->getStore()->getPermissions()->where('name', 'delete-post'));
        $this->assertSame('delete the post permanently', $this->getAccessService()->getStore()->getPermissions()->where('name', 'delete-post')->first()->description);
    }

    /**
     * @covers ::createManyPermissions
     *
     * @test
     */
    public function it_syncs_permissions_after_creating_many_permissions_at_once()
    {
        $this->getAccessService()->createManyPermissions([
            ['name' => 'edit-post', 'title' => 'Edit Post'],
            ['name' => 'delete-post', 'title' => 'Delete Post', 'description' => 'delete the post permanently'],
        ]);

        $this->assertCount(2, $this->getAccessService()->getStore()->getPermissions());
    }

    /**
     * @covers ::createManyPermissions
     *
     * @dataProvider createManyPermissionsProvider
     *
     * @test
     */
    public function it_throws_exception_when_invalid_attributes_are_passed_while_creating_many_permissions_at_once(array $permissions, string $expectedErrorMessage = null, \Closure $init = null)
    {
        if ($init) {
        $init();
        }

        $this->assertException(InvalidAttributesException::class, function () use ($permissions) {
            $this->getAccessService()->createManyPermissions($permissions);
        }, $expectedErrorMessage);
    }

    public function createManyPermissionsProvider(): array
    {
        return [
            'Empty Permissions Array' => [
                [],
                'The permissions field must have at least 1 items.',
            ],
            'Required Permission Name' => [
                [
                    ['name' => 'edit-post', 'title' => 'Edit Post'],
                    ['title' => 'View Post'],
                ],
                'The permissions.1.name field is required.',
            ],
            'Only String Permission Name' => [
                [
                    ['name' => 'edit-post', 'title' => 'Edit Post'],
                    ['name' => 34, 'title' => 'View Post'],
                ],
                'The permissions.1.name field must be a string.',
            ],
            'Only String Permission Title' => [
                [
                    ['name' => 'edit-post', 'title' => 'Edit Post'],
                    ['name' => 'delete-post', 'title' => 64],
                ],
                'The permissions.1.title field must be a string.',
            ],
            'Unique Permission Name' => [
                [
                    ['name' => 'edit-post', 'title' => 'Edit Post'],
                    ['name' => 'delete-post', 'title' => 'New Delete Post'],
                ],
                'The permissions.1.name has already been taken.',
                fn () => $this->getAccessService()->createPermission(['name' => 'delete-post', 'title' => 'Delete Post']),
            ],
            'Duplicate Permission Name' => [
                [
                    ['name' => 'edit-post', 'title' => 'Edit Post'],
                    ['name' => 'delete-post', 'title' => 'Delete Post'],
                    ['name' => 'delete-post', 'title' => 'New Delete Post'],
                ],
                'Duplicate permissions passed. Please make sure permission name is unique.',
            ],
        ];
    }

    /**
     * @covers ::updatePermission
     *
     * @test
     */
    public function it_allows_updating_permission()
    {
        $permission = $this->getAccessService()->createPermission([
            'name' => 'view-posts',
            'title' => 'View All Posts',
        ]);

        $this->getAccessService()->updatePermission($permission, [
            'name' => 'view-all-posts',
        ]);

        $this->assertCount(1, $this->getAccessService()->getStore()->getPermissions()->where('id', $permission->id));
        $this->assertSame('view-all-posts', $this->getAccessService()->getStore()->getPermissions()->where('id', $permission->id)->first()->name);
    }

    /**
     * @covers ::updatePermission
     *
     * @test
     */
    public function it_syncs_permission_after_update()
    {
        $permission1 = $this->getAccessService()->createPermission([
            'name' => 'view-posts',
            'title' => 'View Posts',
        ]);

        $permission2 = $this->getAccessService()->createPermission([
            'name' => 'edit-posts',
            'title' => 'Edit Posts',
        ]);

        $this->getAccessService()->updatePermission($permission1, [
            'name' => 'view-all-posts',
        ]);

        $this->assertCount(2, $this->getAccessService()->getStore()->getPermissions());
        $this->assertCount(1, $this->getAccessService()->getStore()->getPermissions()->where('id', $permission1->id));
        $this->assertCount(1, $this->getAccessService()->getStore()->getPermissions()->where('id', $permission2->id));
    }

    /**
     * @covers ::updatePermission
     *
     * @dataProvider updatePermissionDataProvider
     *
     * @test
     */
    public function it_throws_exception_when_invalid_attributes_are_provided_to_update_a_permission(array $attributes, string $expectedErrorMessage = null, \Closure $init = null)
    {
        if ($init) {
        $init();
        }

        $permission = $this->getAccessService()->createPermission([
            'name' => 'admin',
            'title' => 'Admin',
        ]);

        $this->assertException(InvalidAttributesException::class, function () use ($permission, $attributes) {
            $this->getAccessService()->updatePermission($permission, $attributes);
        }, $expectedErrorMessage);
    }

    public function updatePermissionDataProvider(): array
    {
        return [
            'Only String Name' => [
                ['name' => 467, 'title' => 'Edit Post'],
                'The name field must be a string.',
            ],
            'Only String Title' => [
                ['name' => 'edit-post', 'title' => 236],
                'The title field must be a string.',
            ],
            'Unique Name' => [
                ['name' => 'edit-post', 'title' => 'New Edit Post'],
                'The name has already been taken.',
                fn () => Permission::factory()->createFake(['name' => 'edit-post']),
            ],
            'Only String Description' => [
                ['name' => 'edit-post', 'title' => 'Edit Post', 'description' => 6346],
                'The description field must be a string.',
            ],
        ];
    }

    /**
     * @covers ::updatePermission
     *
     * @test
     */
    public function it_throws_exception_if_passed_permission_is_not_valid_while_updating_permission()
    {
        $permission = Permission::factory()->make();

        $this->expectException(InvalidPermissionException::class);
        $this->getAccessService()->updatePermission($permission, [
            'name' => 'view-company',
        ]);
    }

    /**
     * @covers ::updatePermission
     *
     * @test
     */
    public function it_throws_exception_if_permission_could_not_be_updated_even_after_passing_the_validation()
    {
        $permission = $this->getAccessService()->createPermission([
            'name' => 'view-company',
            'title' => 'View Company',
        ]);

        $validatorMock = \Mockery::mock();
        $validatorMock->shouldReceive('fails')->once()->andReturn(false);
        $validatorMock->shouldReceive('stopOnFirstFailure')->once()->andReturnSelf();

        $validatorMock->shouldReceive('safe')->once()->andReturnUsing(function () use ($permission) {
            $this->getAccessService()->deletePermission($permission);

            return new ValidatedInput([
                'name' => 'view-company',
            ]);
        });

        Validator::shouldReceive('make')->once()->andReturn($validatorMock);

        $this->expectException(UpdateProcessFailedException::class);
        $this->getAccessService()->updatePermission($permission, [
            'name' => 'Admin',
        ]);
    }

    /**
     * @covers ::deleteRole
     *
     * @test
     */
    public function it_allows_deleting_a_role_and_returns_true_on_success()
    {
        $role1 = $this->getAccessService()->createRole([
            'name' => 'admin',
            'title' => 'Admin',
        ]);
        $role2 = $this->getAccessService()->createRole([
            'name' => 'manager',
            'title' => 'Manager',
        ]);
        $role3 = $this->getAccessService()->createRole([
            'name' => 'editor',
            'title' => 'Editor',
        ]);

        $this->assertCount(3, $this->getAccessService()->getStore()->getRoles());

        $this->assertTrue($this->getAccessService()->deleteRole($role1));
        $this->assertTrue($this->getAccessService()->deleteRole($role2->name));
        $this->assertTrue($this->getAccessService()->deleteRole($role3->id));

        $this->assertCount(0, $this->getAccessService()->getStore()->getRoles());
    }

    /**
     * @covers ::deleteRole
     *
     * @test
     */
    public function it_returns_false_if_role_is_invalid_or_it_could_not_update_the_role()
    {
        $role = Role::factory()->make();

        $this->assertFalse($this->getAccessService()->deleteRole($role));
    }

    /**
     * @covers ::deletePermission
     *
     * @test
     */
    public function it_allows_deleting_a_permission_and_returns_true_on_success()
    {
        $permission1 = $this->getAccessService()->createPermission([
            'name' => 'edit-post',
            'title' => 'Edit Post',
        ]);
        $permission2 = $this->getAccessService()->createPermission([
            'name' => 'view-post',
            'title' => 'View Post',
        ]);
        $permission3 = $this->getAccessService()->createPermission([
            'name' => 'delete-post',
            'title' => 'Delete Post',
        ]);

        $this->assertCount(3, $this->getAccessService()->getStore()->getPermissions());

        $this->assertTrue($this->getAccessService()->deletePermission($permission1));
        $this->assertTrue($this->getAccessService()->deletePermission($permission2->name));
        $this->assertTrue($this->getAccessService()->deletePermission($permission3->id));

        $this->assertCount(0, $this->getAccessService()->getStore()->getPermissions());
    }

    /**
     * @covers ::deletePermission
     *
     * @test
     */
    public function it_returns_false_if_permission_is_invalid_or_it_could_not_update_the_permission()
    {
        $permission = Permission::factory()->make();

        $this->assertFalse($this->getAccessService()->deletePermission($permission));
    }

    /**
     * @covers ::allow
     *
     * @test
     */
    public function it_returns_a_valid_allow_permission_class_object()
    {
        $role = $this->getAccessService()->createRole([
            'name' => 'admin',
            'title' => 'Admin',
        ]);

        $this->assertInstanceOf(AllowPermission::class, $this->getAccessService()->allow($role));
        $this->assertInstanceOf(AllowPermission::class, $this->getAccessService()->allow($role->name));
    }

    /**
     * @covers ::allow
     *
     * @test
     */
    public function it_throws_exception_if_given_role_is_not_valid_or_not_found_while_allowing_a_permission()
    {
        $this->assertException(InvalidRoleException::class, function () {
            $role = Role::factory()->make();

            $this->assertInstanceOf(AllowPermission::class, $this->getAccessService()->allow($role));
        });

        $this->assertException(InvalidRoleException::class, function () {
            $this->assertInstanceOf(AllowPermission::class, $this->getAccessService()->allow(567));
        });

        $this->assertException(InvalidRoleException::class, function () {
            $this->assertInstanceOf(AllowPermission::class, $this->getAccessService()->allow('role-name'));
        });
    }

    /**
     * @covers ::disallow
     *
     * @test
     */
    public function it_returns_a_valid_disallow_permission_class_object()
    {
        $role = $this->getAccessService()->createRole([
            'name' => 'admin',
            'title' => 'Admin',
        ]);

        $this->assertInstanceOf(DisallowPermission::class, $this->getAccessService()->disallow($role));
        $this->assertInstanceOf(DisallowPermission::class, $this->getAccessService()->disallow($role->name));
    }

    /**
     * @covers ::disallow
     *
     * @test
     */
    public function it_throws_exception_if_given_role_is_not_valid_or_not_found_while_disallowing_a_permission()
    {
        $this->assertException(InvalidRoleException::class, function () {
            $role = Role::factory()->make();

            $this->assertInstanceOf(DisallowPermission::class, $this->getAccessService()->disallow($role));
        });

        $this->assertException(InvalidRoleException::class, function () {
            $this->assertInstanceOf(DisallowPermission::class, $this->getAccessService()->disallow(567));
        });

        $this->assertException(InvalidRoleException::class, function () {
            $this->assertInstanceOf(DisallowPermission::class, $this->getAccessService()->disallow('role-name'));
        });
    }

    /**
     * @covers ::forbid
     *
     * @test
     */
    public function it_returns_a_valid_forbid_permission_class_object()
    {
        $role = $this->getAccessService()->createRole([
            'name' => 'admin',
            'title' => 'Admin',
        ]);

        $this->assertInstanceOf(ForbidPermission::class, $this->getAccessService()->forbid($role));
        $this->assertInstanceOf(ForbidPermission::class, $this->getAccessService()->forbid($role->name));
    }

    /**
     * @covers ::forbid
     *
     * @test
     */
    public function it_throws_exception_if_given_role_is_not_valid_or_not_found_while_forbidding_a_permission()
    {
        $this->assertException(InvalidRoleException::class, function () {
            $role = Role::factory()->make();

            $this->assertInstanceOf(ForbidPermission::class, $this->getAccessService()->forbid($role));
        });

        $this->assertException(InvalidRoleException::class, function () {
            $this->assertInstanceOf(ForbidPermission::class, $this->getAccessService()->forbid(567));
        });

        $this->assertException(InvalidRoleException::class, function () {
            $this->assertInstanceOf(ForbidPermission::class, $this->getAccessService()->disallow('role-name'));
        });
    }

    /**
     * @covers ::assign
     *
     * @test
     */
    public function it_returns_a_valid_assign_role_class_object()
    {
        $role1 = $this->getAccessService()->createRole([
            'name' => 'admin',
            'title' => 'Admin',
        ]);

        $role2 = $this->getAccessService()->createRole([
            'name' => 'manager',
            'title' => 'Manager',
            'roleables' => [
                Company::class,
            ],
        ]);

        $this->assertInstanceOf(AssignRole::class, $this->getAccessService()->assign($role1));
        $this->assertInstanceOf(AssignRole::class, $this->getAccessService()->assign($role1->name));

        $this->assertInstanceOf(AssignRole::class, $this->getAccessService()->assign($role2, Company::factory()->create()));
        $this->assertInstanceOf(AssignRole::class, $this->getAccessService()->assign($role1->name, Company::factory()->create()));
    }

    /**
     * @covers ::assign
     *
     * @test
     */
    public function it_throws_exception_if_given_role_is_not_valid_or_not_found_while_assigning_a_role()
    {
        $this->assertException(InvalidRoleException::class, function () {
            $role = Role::factory()->make();

            $this->assertInstanceOf(AssignRole::class, $this->getAccessService()->assign($role));
        });

        $this->assertException(InvalidRoleException::class, function () {
            $this->assertInstanceOf(AssignRole::class, $this->getAccessService()->assign(567));
        });

        $this->assertException(InvalidRoleException::class, function () {
            $this->assertInstanceOf(AssignRole::class, $this->getAccessService()->assign('role-name'));
        });
    }

    /**
     * @covers ::retract
     *
     * @test
     */
    public function it_returns_a_valid_retract_role_class_object()
    {
        $role1 = $this->getAccessService()->createRole([
            'name' => 'admin',
            'title' => 'Admin',
        ]);

        $role2 = $this->getAccessService()->createRole([
            'name' => 'manager',
            'title' => 'Manager',
            'roleables' => [
                Company::class,
            ],
        ]);

        $this->assertInstanceOf(RetractRole::class, $this->getAccessService()->retract($role1));
        $this->assertInstanceOf(RetractRole::class, $this->getAccessService()->retract($role1->name));

        $this->assertInstanceOf(RetractRole::class, $this->getAccessService()->retract($role2, Company::factory()->create()));
        $this->assertInstanceOf(RetractRole::class, $this->getAccessService()->retract($role1->name, Company::factory()->create()));
    }

    /**
     * @covers ::retract
     *
     * @test
     */
    public function it_throws_exception_if_given_role_is_not_valid_or_not_found_while_retracting_a_role()
    {
        $this->assertException(InvalidRoleException::class, function () {
            $role = Role::factory()->make();

            $this->assertInstanceOf(RetractRole::class, $this->getAccessService()->retract($role));
        });

        $this->assertException(InvalidRoleException::class, function () {
            $this->assertInstanceOf(RetractRole::class, $this->getAccessService()->retract(567));
        });

        $this->assertException(InvalidRoleException::class, function () {
            $this->assertInstanceOf(RetractRole::class, $this->getAccessService()->retract('role-name'));
        });
    }

    /**
     * @covers ::is
     *
     * @test
     */
    public function it_returns_a_valid_user_role_check_class_object()
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(UserRoleCheck::class, $this->getAccessService()->is($user));
    }

    /**
     * @covers ::is
     *
     * @test
     */
    public function it_throws_exception_if_user_id_is_invalid_or_not_available_while_checking_user_role()
    {
        $user = new User();

        $this->expectException(InvalidUserException::class);
        $this->assertInstanceOf(UserRoleCheck::class, $this->getAccessService()->is($user));
    }

    /**
     * @covers ::canRole
     *
     * @test
     */
    public function it_returns_a_valid_role_permission_check_class_object()
    {
        $role = $this->getAccessService()->createRole([
            'name' => 'admin',
            'title' => 'Title',
        ]);

        $this->assertInstanceOf(RolePermissionCheck::class, $this->getAccessService()->canRole($role));
        $this->assertInstanceOf(RolePermissionCheck::class, $this->getAccessService()->canRole($role->name));
    }

    /**
     * @covers ::canRole
     *
     * @test
     */
    public function it_throws_exception_if_role_is_invalid_while_checking_permission_of_a_role()
    {
        $this->assertException(InvalidRoleException::class, function () {
            $role = Role::factory()->make();

            $this->assertInstanceOf(RolePermissionCheck::class, $this->getAccessService()->canRole($role));
        });

        $this->assertException(InvalidRoleException::class, function () {
            $this->assertInstanceOf(RolePermissionCheck::class, $this->getAccessService()->canRole(567));
        });

        $this->assertException(InvalidRoleException::class, function () {
            $this->assertInstanceOf(RolePermissionCheck::class, $this->getAccessService()->canRole('role-name'));
        });
    }

    /**
     * @covers ::canUser
     *
     * @test
     */
    public function it_returns_a_valid_user_permission_check_class_object()
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(UserPermissionCheck::class, $this->getAccessService()->canUser($user));
    }

    /**
     * @covers ::is
     *
     * @test
     */
    public function it_throws_exception_if_user_id_is_invalid_or_not_available_while_checking_user_permission()
    {
        $user = new User();

        $this->expectException(InvalidUserException::class);
        $this->assertInstanceOf(UserPermissionCheck::class, $this->getAccessService()->canUser($user));
    }

    /**
     * @covers ::resetRole
     *
     * @test
     */
    public function it_resets_a_role()
    {
        $role1 = $this->getAccessService()->createRole([
            'name' => 'manager',
            'title' => 'Manager',
            'roleables' => [
                Company::class,
            ],
        ]);

        $role2 = $this->getAccessService()->createRole([
            'name' => 'ceo',
            'title' => 'CEO',
            'roleables' => [
                Company::class,
            ],
        ]);

        $this->getAccessService()->createManyPermissions([
            ['name' => 'view-company', 'title' => 'View Company'],
            ['name' => 'edit-company', 'title' => 'Edit Company'],
            ['name' => 'delete-company', 'title' => 'Delete Company'],
        ]);

        $this->getAccessService()->allow($role1)->to('view-company');
        $this->getAccessService()->allow($role1)->to('edit-company');

        $this->getAccessService()->allow($role2)->to('view-company');
        $this->getAccessService()->allow($role2)->to('edit-company');
        $this->getAccessService()->allow($role2)->to('delete-company');

        $this->assertCount(2, $this->getAccessService()->getStore()->getAllowedPermissionsOf($role1));
        $this->assertCount(3, $this->getAccessService()->getStore()->getAllowedPermissionsOf($role2));

        $this->getAccessService()->resetRole($role1);

        $this->assertCount(0, $this->getAccessService()->getStore()->getAllowedPermissionsOf($role1));
        $this->assertCount(3, $this->getAccessService()->getStore()->getAllowedPermissionsOf($role2));
    }

    /**
     * @covers ::resetUser
     *
     * @test
     */
    public function it_resets_a_user()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $role1 = $this->getAccessService()->createRole([
            'name' => 'manager',
            'title' => 'Manager',
            'roleables' => [
                Company::class,
            ],
        ]);

        $role3 = $this->getAccessService()->createRole([
            'name' => 'customer',
            'title' => 'Customer',
        ]);

        $role2 = $this->getAccessService()->createRole([
            'name' => 'ceo',
            'title' => 'CEO',
            'roleables' => [
                Company::class,
            ],
        ]);

        $this->getAccessService()->createManyPermissions([
            ['name' => 'view-company', 'title' => 'View Company'],
            ['name' => 'edit-company', 'title' => 'Edit Company'],
            ['name' => 'delete-company', 'title' => 'Delete Company'],
            ['name' => 'purchase-products', 'title' => 'Purchase Products'],
        ]);

        $this->getAccessService()->allow($role1)->to('view-company');
        $this->getAccessService()->allow($role1)->to('edit-company');

        $this->getAccessService()->allow($role2)->to('view-company');
        $this->getAccessService()->allow($role2)->to('edit-company');
        $this->getAccessService()->allow($role2)->to('delete-company');

        $this->getAccessService()->allow($role3)->to('purchase-products');

        $company = Company::factory()->create();

        $this->getAccessService()->assign($role1, $company)->to($user1);
        $this->getAccessService()->assign($role3)->to($user1);
        $this->getAccessService()->assign($role2, $company)->to($user2);

        $this->assertCount(3, $user1->permissions());
        $this->assertCount(3, $user2->permissions());
        $this->assertCount(2, $user1->roles()->get());
        $this->assertCount(1, $user2->roles()->get());

        $this->getAccessService()->resetUser($user1);

        $this->assertCount(0, $user1->permissions());
        $this->assertCount(3, $user2->permissions());
        $this->assertCount(0, $user1->roles()->get());
        $this->assertCount(1, $user2->roles()->get());
    }
}
