<?php

namespace Back2Lobby\AccessControl\Tests;

use Back2Lobby\AccessControl\Facades\AccessControlFacade as AccessControl;
use Back2Lobby\AccessControl\Models\Permission;
use Back2Lobby\AccessControl\Models\Role;
use Back2Lobby\AccessControl\Store\StoreService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * @coversDefaultClass \Back2Lobby\AccessControl\Store\StoreService
 */
class StoreServiceTest extends BaseTestCase
{
    protected StoreService $store;

    public function setUp(): void
    {
        parent::setUp();

        $this->store = StoreService::getInstance();
    }

    /**
     * @covers ::getInstance
     *
     * @test
     */
    public function it_is_a_singleton()
    {
        AccessControl::createManyRoles([
            ['name' => 'admin', 'title' => 'Admin'],
            ['name' => 'manager', 'title' => 'Manager'],
        ]);

        $this->assertCount(2, AccessControl::getRoles());
        $this->assertCount(2, StoreService::getInstance()->getRoles());

        AccessControl::clearCache();
        AccessControl::reset();

        $this->assertCount(0, AccessControl::getRoles());
        $this->assertCount(0, StoreService::getInstance()->getRoles());

        $this->assertNull(Cache::get('access-store'));
    }

    /**
     * @covers ::sync
     *
     * @test
     */
    public function it_syncs_successfully_and_cache_after_it()
    {
        DB::table('roles')->insert([
            ['name' => 'admin', 'title' => 'Admin'],
            ['name' => 'manager', 'title' => 'Manager'],
        ]);

        DB::table('permissions')->insert([
            ['name' => 'edit-post', 'title' => 'Edit Post'],
            ['name' => 'delete-post', 'title' => 'Delete Post'],
        ]);

        DB::table('permission_role')->insert([
            ['role_id' => 1, 'permission_id' => 1],
            ['role_id' => 1, 'permission_id' => 2],
            ['role_id' => 2, 'permission_id' => 1],
        ]);

        $this->assertSame(2, DB::table('roles')->count());

        $this->assertCount(0, $this->store->getRoles());
        $this->assertCount(0, $this->store->getPermissions());
        $this->assertCount(0, $this->store->getMap());

        $this->assertNull(Cache::get('access-store'));

        $this->store->sync();

        $this->assertNotNull(Cache::get('access-store'));

        $this->assertCount(2, $this->store->getRoles());
        $this->assertNotNull($this->store->getRole('admin'));
        $this->assertNotNull($this->store->getRole('manager'));

        $this->assertCount(2, $this->store->getPermissions());
        $this->assertNotNull($this->store->getPermission('edit-post'));
        $this->assertNotNull($this->store->getPermission('delete-post'));

        $this->assertCount(3, $this->store->getMap());
        $this->assertCount(2, $this->store->getAllowedPermissionsOf($this->store->getRole('admin')));
        $this->assertCount(1, $this->store->getAllowedPermissionsOf($this->store->getRole('manager')));

        $this->assertSameArray(['edit-post', 'delete-post'], $this->store->getAllowedPermissionsOf($this->store->getRole('admin'))->pluck('name'));
        $this->assertSameArray(['edit-post'], $this->store->getAllowedPermissionsOf($this->store->getRole('manager'))->pluck('name'));
    }

    /**
     * @covers ::cache
     *
     * @test
     */
    public function it_cache_successfully_and_forgets_after_one_day()
    {
        DB::table('roles')->insert([
            ['name' => 'admin', 'title' => 'Admin'],
            ['name' => 'manager', 'title' => 'Manager'],
        ]);

        DB::table('permissions')->insert([
            ['name' => 'edit-post', 'title' => 'Edit Post'],
            ['name' => 'delete-post', 'title' => 'Delete Post'],
        ]);

        DB::table('permission_role')->insert([
            ['role_id' => 1, 'permission_id' => 1],
            ['role_id' => 1, 'permission_id' => 2],
            ['role_id' => 2, 'permission_id' => 1],
        ]);

        $this->store->sync();

        $this->assertNotNull(Cache::get('access-store'));

        $this->travelTo(now()->addDay()->addSecond());

        $this->assertNull(Cache::get('access-store'));
    }

    /**
     * @covers ::clearCache
     *
     * @test
     */
    public function it_clears_cache()
    {
        DB::table('roles')->insert([
            ['name' => 'admin', 'title' => 'Admin'],
            ['name' => 'manager', 'title' => 'Manager'],
        ]);

        DB::table('permissions')->insert([
            ['name' => 'edit-post', 'title' => 'Edit Post'],
            ['name' => 'delete-post', 'title' => 'Delete Post'],
        ]);

        DB::table('permission_role')->insert([
            ['role_id' => 1, 'permission_id' => 1],
            ['role_id' => 1, 'permission_id' => 2],
            ['role_id' => 2, 'permission_id' => 1],
        ]);

        $this->store->sync();

        $this->store->clearCache();

        $this->assertNull(Cache::get('access-store'));
    }

    /**
     * @covers ::reset
     *
     * @test
     */
    public function it_resets_the_store_service_object()
    {
        AccessControl::createManyRoles([
            ['name' => 'admin', 'title' => 'Admin'],
            ['name' => 'manager', 'title' => 'Manager'],
        ]);

        $this->assertCount(2, $this->store->getRoles());

        $this->store->reset();

        $this->assertCount(0, $this->store->getRoles());
    }

    /**
     * @covers ::getRoles
     *
     * @test
     */
    public function it_gives_all_the_roles()
    {
        AccessControl::createManyRoles([
            ['name' => 'admin', 'title' => 'Admin'],
            ['name' => 'manager', 'title' => 'Manager'],
        ]);

        $this->assertCount(2, $this->store->getRoles());
    }

    /**
     * @covers ::getPermissions
     *
     * @test
     */
    public function it_gives_all_the_permissions()
    {
        AccessControl::createManyPermissions([
            ['name' => 'view-company', 'title' => 'View Company'],
            ['name' => 'delete-company', 'title' => 'Delete Company'],
        ]);

        $this->assertCount(2, $this->store->getPermissions());
    }

    /**
     * @covers ::getMap
     *
     * @test
     */
    public function it_gives_all_the_maps()
    {
        AccessControl::createManyRoles([
            ['name' => 'admin', 'title' => 'Admin'],
            ['name' => 'manager', 'title' => 'Manager'],
        ]);

        AccessControl::createManyPermissions([
            ['name' => 'edit-company', 'title' => 'Edit Company'],
            ['name' => 'delete-company', 'title' => 'Delete Company'],
        ]);

        AccessControl::allow('admin')->to('edit-company');
        AccessControl::allow('admin')->to('delete-company');
        AccessControl::allow('manager')->to('edit-company');

        $this->assertCount(3, $this->store->getMap());
    }

    /**
     * @covers ::getRole
     *
     * @test
     */
    public function it_returns_a_role_if_found_otherwise_returns_null()
    {
        $role1 = AccessControl::createRole(['name' => 'admin', 'title' => 'Admin']);
        $role2 = AccessControl::createRole(['name' => 'manager', 'title' => 'Manager']);

        $this->assertSame('admin', $this->store->getRole($role1)->name);
        $this->assertSame('manager', $this->store->getRole($role2)->name);

        $this->assertSame('admin', $this->store->getRole($role1->id)->name);
        $this->assertSame('manager', $this->store->getRole($role2->id)->name);

        $this->assertSame('admin', $this->store->getRole($role1->name)->name);
        $this->assertSame('manager', $this->store->getRole($role2->name)->name);

        $this->assertNull($this->store->getRole('non-existent-role'));
        $this->assertNull($this->store->getRole(new Role()));
        $this->assertNull($this->store->getRole(Role::factory()->make(['name' => 'some-random-role-name'])));
    }

    /**
     * @covers ::getPermission
     *
     * @test
     */
    public function it_returns_a_permission_if_found_otherwise_returns_null()
    {
        $permission1 = AccessControl::createPermission(['name' => 'edit-post', 'title' => 'Edit Post']);
        $permission2 = AccessControl::createPermission(['name' => 'delete-post', 'title' => 'Delete Post']);

        $this->assertSame('edit-post', $this->store->getPermission($permission1)->name);
        $this->assertSame('delete-post', $this->store->getPermission($permission2)->name);

        $this->assertSame('edit-post', $this->store->getPermission($permission1->id)->name);
        $this->assertSame('delete-post', $this->store->getPermission($permission2->id)->name);

        $this->assertSame('edit-post', $this->store->getPermission($permission1->name)->name);
        $this->assertSame('delete-post', $this->store->getPermission($permission2->name)->name);

        $this->assertNull($this->store->getPermission('non-existent-permission'));
        $this->assertNull($this->store->getPermission(new Permission()));
        $this->assertNull($this->store->getPermission(Permission::factory()->make(['name' => 'some-random-permission-name'])));
    }

    /**
     * @covers ::getAllPermissionsOf
     *
     * @test
     */
    public function it_returns_all_permissions_of_a_role_including_forbidden_and_indirect_ones()
    {
        AccessControl::createManyRoles([
            ['name' => 'admin', 'title' => 'Admin'],
            ['name' => 'manager', 'title' => 'Manager'],
            ['name' => 'user', 'title' => 'User'],
        ]);

        AccessControl::createManyPermissions([
            ['name' => '*', 'title' => 'Do Anything'],
            ['name' => 'view-company', 'title' => 'View Company'],
            ['name' => 'edit-company', 'title' => 'Edit Company'],
            ['name' => 'delete-company', 'title' => 'Delete Company'],
        ]);

        AccessControl::allow('admin')->superPermission();
        AccessControl::allow('manager')->superPermission();
        AccessControl::forbid('manager')->to('delete-company');
        AccessControl::allow('user')->to('view-company');

        $this->assertCount(4, $this->store->getAllPermissionsOf($this->store->getRole('admin')));
        $this->assertCount(4, $this->store->getAllPermissionsOf($this->store->getRole('manager')));
        $this->assertCount(1, $this->store->getAllPermissionsOf($this->store->getRole('user')));
    }

    /**
     * @covers ::getAllowedPermissionsOf
     *
     * @test
     */
    public function it_returns_only_allowed_permissions_of_a_role()
    {
        AccessControl::createManyRoles([
            ['name' => 'admin', 'title' => 'Admin'],
            ['name' => 'manager', 'title' => 'Manager'],
            ['name' => 'user', 'title' => 'User'],
        ]);

        AccessControl::createManyPermissions([
            ['name' => '*', 'title' => 'Do Anything'],
            ['name' => 'view-company', 'title' => 'View Company'],
            ['name' => 'edit-company', 'title' => 'Edit Company'],
            ['name' => 'delete-company', 'title' => 'Delete Company'],
        ]);

        AccessControl::allow('admin')->superPermission();
        AccessControl::allow('manager')->superPermission();
        AccessControl::forbid('manager')->to('delete-company');
        AccessControl::allow('user')->to('view-company');

        $this->assertCount(4, $this->store->getAllPermissionsOf($this->store->getRole('admin')));
        $this->assertCount(4, $this->store->getAllPermissionsOf($this->store->getRole('manager')));
        $this->assertCount(1, $this->store->getAllPermissionsOf($this->store->getRole('user')));

        $this->assertCount(4, $this->store->getAllowedPermissionsOf($this->store->getRole('admin')));
        $this->assertCount(3, $this->store->getAllowedPermissionsOf($this->store->getRole('manager')));
        $this->assertCount(1, $this->store->getAllowedPermissionsOf($this->store->getRole('user')));
    }

    /**
     * @covers ::getDirectlyAllowedPermissionsOf
     *
     * @test
     */
    public function it_returns_only_directly_allowed_permissions_of_a_role()
    {
        AccessControl::createManyRoles([
            ['name' => 'admin', 'title' => 'Admin'],
            ['name' => 'manager', 'title' => 'Manager'],
            ['name' => 'user', 'title' => 'User'],
        ]);

        AccessControl::createManyPermissions([
            ['name' => '*', 'title' => 'Do Anything'],
            ['name' => 'view-company', 'title' => 'View Company'],
            ['name' => 'edit-company', 'title' => 'Edit Company'],
            ['name' => 'delete-company', 'title' => 'Delete Company'],
        ]);

        AccessControl::allow('admin')->superPermission();
        AccessControl::allow('manager')->superPermission();
        AccessControl::forbid('manager')->to('delete-company');
        AccessControl::allow('user')->to('view-company');

        $this->assertCount(4, $this->store->getAllowedPermissionsOf($this->store->getRole('admin')));
        $this->assertCount(3, $this->store->getAllowedPermissionsOf($this->store->getRole('manager')));
        $this->assertCount(1, $this->store->getAllowedPermissionsOf($this->store->getRole('user')));

        $this->assertCount(1, $this->store->getDirectlyAllowedPermissionsOf($this->store->getRole('admin')));
        $this->assertCount(1, $this->store->getDirectlyAllowedPermissionsOf($this->store->getRole('manager')));
        $this->assertCount(1, $this->store->getDirectlyAllowedPermissionsOf($this->store->getRole('user')));
    }

    /**
     * @covers ::getIndirectlyAllowedPermissionsOf
     *
     * @test
     */
    public function it_returns_only_indirectly_allowed_permissions_of_a_role()
    {
        AccessControl::createManyRoles([
            ['name' => 'admin', 'title' => 'Admin'],
            ['name' => 'manager', 'title' => 'Manager'],
            ['name' => 'user', 'title' => 'User'],
        ]);

        AccessControl::createManyPermissions([
            ['name' => '*', 'title' => 'Do Anything'],
            ['name' => 'view-company', 'title' => 'View Company'],
            ['name' => 'edit-company', 'title' => 'Edit Company'],
            ['name' => 'delete-company', 'title' => 'Delete Company'],
        ]);

        AccessControl::allow('admin')->superPermission();
        AccessControl::allow('manager')->superPermission();
        AccessControl::forbid('manager')->to('delete-company');
        AccessControl::allow('user')->to('view-company');

        $this->assertCount(4, $this->store->getAllowedPermissionsOf($this->store->getRole('admin')));
        $this->assertCount(3, $this->store->getAllowedPermissionsOf($this->store->getRole('manager')));
        $this->assertCount(1, $this->store->getAllowedPermissionsOf($this->store->getRole('user')));

        $this->assertCount(3, $this->store->getIndirectlyAllowedPermissionsOf($this->store->getRole('admin')));
        $this->assertCount(2, $this->store->getIndirectlyAllowedPermissionsOf($this->store->getRole('manager')));
        $this->assertCount(0, $this->store->getIndirectlyAllowedPermissionsOf($this->store->getRole('user')));
    }

    /**
     * @covers ::getForbiddenPermissionsOf
     *
     * @test
     */
    public function it_returns_only_forbidden_permissions_of_a_role()
    {
        AccessControl::createManyRoles([
            ['name' => 'admin', 'title' => 'Admin'],
            ['name' => 'manager', 'title' => 'Manager'],
            ['name' => 'user', 'title' => 'User'],
        ]);

        AccessControl::createManyPermissions([
            ['name' => '*', 'title' => 'Do Anything'],
            ['name' => 'view-company', 'title' => 'View Company'],
            ['name' => 'edit-company', 'title' => 'Edit Company'],
            ['name' => 'delete-company', 'title' => 'Delete Company'],
        ]);

        AccessControl::allow('admin')->superPermission();
        AccessControl::allow('manager')->superPermission();
        AccessControl::forbid('manager')->to('delete-company');
        AccessControl::forbid('user')->superPermission();
        AccessControl::allow('user')->to('view-company');

        $this->assertCount(4, $this->store->getAllPermissionsOf($this->store->getRole('admin')));
        $this->assertCount(4, $this->store->getAllPermissionsOf($this->store->getRole('manager')));
        $this->assertCount(4, $this->store->getAllPermissionsOf($this->store->getRole('user')));

        $this->assertCount(0, $this->store->getForbiddenPermissionsOf($this->store->getRole('admin')));
        $this->assertCount(1, $this->store->getForbiddenPermissionsOf($this->store->getRole('manager')));
        $this->assertCount(3, $this->store->getForbiddenPermissionsOf($this->store->getRole('user')));
    }

    /**
     * @covers ::getDirectlyForbiddenPermissionsOf
     *
     * @test
     */
    public function it_returns_only_directly_forbidden_permissions_of_a_role()
    {
        AccessControl::createManyRoles([
            ['name' => 'admin', 'title' => 'Admin'],
            ['name' => 'manager', 'title' => 'Manager'],
            ['name' => 'user', 'title' => 'User'],
        ]);

        AccessControl::createManyPermissions([
            ['name' => '*', 'title' => 'Do Anything'],
            ['name' => 'view-company', 'title' => 'View Company'],
            ['name' => 'edit-company', 'title' => 'Edit Company'],
            ['name' => 'delete-company', 'title' => 'Delete Company'],
        ]);

        AccessControl::allow('admin')->superPermission();
        AccessControl::allow('manager')->superPermission();
        AccessControl::forbid('manager')->to('delete-company');
        AccessControl::forbid('user')->superPermission();
        AccessControl::allow('user')->to('view-company');

        $this->assertCount(0, $this->store->getForbiddenPermissionsOf($this->store->getRole('admin')));
        $this->assertCount(1, $this->store->getForbiddenPermissionsOf($this->store->getRole('manager')));
        $this->assertCount(3, $this->store->getForbiddenPermissionsOf($this->store->getRole('user')));

        $this->assertCount(0, $this->store->getDirectlyForbiddenPermissionsOf($this->store->getRole('admin')));
        $this->assertCount(1, $this->store->getDirectlyForbiddenPermissionsOf($this->store->getRole('manager')));
        $this->assertCount(1, $this->store->getDirectlyForbiddenPermissionsOf($this->store->getRole('user')));
    }

    /**
     * @covers ::getIndirectlyForbiddenPermissionsOf
     *
     * @test
     */
    public function it_returns_only_indirectly_forbidden_permissions_of_a_role()
    {
        AccessControl::createManyRoles([
            ['name' => 'admin', 'title' => 'Admin'],
            ['name' => 'manager', 'title' => 'Manager'],
            ['name' => 'user', 'title' => 'User'],
        ]);

        AccessControl::createManyPermissions([
            ['name' => '*', 'title' => 'Do Anything'],
            ['name' => 'view-company', 'title' => 'View Company'],
            ['name' => 'edit-company', 'title' => 'Edit Company'],
            ['name' => 'delete-company', 'title' => 'Delete Company'],
        ]);

        AccessControl::allow('admin')->superPermission();
        AccessControl::allow('manager')->superPermission();
        AccessControl::forbid('manager')->to('delete-company');
        AccessControl::forbid('user')->superPermission();
        AccessControl::allow('user')->to('view-company');

        $this->assertCount(0, $this->store->getForbiddenPermissionsOf($this->store->getRole('admin')));
        $this->assertCount(1, $this->store->getForbiddenPermissionsOf($this->store->getRole('manager')));
        $this->assertCount(3, $this->store->getForbiddenPermissionsOf($this->store->getRole('user')));

        $this->assertCount(0, $this->store->getIndirectlyForbiddenPermissionsOf($this->store->getRole('admin')));
        $this->assertCount(0, $this->store->getIndirectlyForbiddenPermissionsOf($this->store->getRole('manager')));
        $this->assertCount(2, $this->store->getIndirectlyForbiddenPermissionsOf($this->store->getRole('user')));
    }

    /**
     * @covers ::getAllRolesOf
     *
     * @test
     */
    public function it_returns_all_roles_of_a_permission_including_forbidden_and_indirect_ones()
    {
        AccessControl::createManyRoles([
            ['name' => 'admin', 'title' => 'Admin'],
            ['name' => 'manager', 'title' => 'Manager'],
            ['name' => 'user', 'title' => 'User'],
        ]);

        AccessControl::createManyPermissions([
            ['name' => '*', 'title' => 'Do Anything'],
            ['name' => 'view-company', 'title' => 'View Company'],
            ['name' => 'edit-company', 'title' => 'Edit Company'],
            ['name' => 'delete-company', 'title' => 'Delete Company'],
        ]);

        AccessControl::allow('admin')->superPermission();
        AccessControl::allow('manager')->superPermission();
        AccessControl::forbid('manager')->to('delete-company');
        AccessControl::allow('user')->to('view-company');

        $this->assertCount(2, $this->store->getAllRolesOf($this->store->getPermission('*')));
        $this->assertCount(3, $this->store->getAllRolesOf($this->store->getPermission('view-company')));
        $this->assertCount(2, $this->store->getAllRolesOf($this->store->getPermission('edit-company')));
        $this->assertCount(2, $this->store->getAllRolesOf($this->store->getPermission('delete-company')));
    }

    /**
     * @covers ::getAllowedRolesOf
     *
     * @test
     */
    public function it_returns_only_allowed_roles_of_a_permission()
    {
        AccessControl::createManyRoles([
            ['name' => 'admin', 'title' => 'Admin'],
            ['name' => 'manager', 'title' => 'Manager'],
            ['name' => 'user', 'title' => 'User'],
        ]);

        AccessControl::createManyPermissions([
            ['name' => '*', 'title' => 'Do Anything'],
            ['name' => 'view-company', 'title' => 'View Company'],
            ['name' => 'edit-company', 'title' => 'Edit Company'],
            ['name' => 'delete-company', 'title' => 'Delete Company'],
        ]);

        AccessControl::allow('admin')->superPermission();
        AccessControl::allow('manager')->superPermission();
        AccessControl::forbid('manager')->to('delete-company');
        AccessControl::allow('user')->to('view-company');

        $this->assertCount(3, $this->store->getAllRolesOf($this->store->getPermission('view-company')));
        $this->assertCount(2, $this->store->getAllRolesOf($this->store->getPermission('edit-company')));
        $this->assertCount(2, $this->store->getAllRolesOf($this->store->getPermission('delete-company')));

        $this->assertCount(3, $this->store->getAllowedRolesOf($this->store->getPermission('view-company')));
        $this->assertCount(2, $this->store->getAllowedRolesOf($this->store->getPermission('edit-company')));
        $this->assertCount(1, $this->store->getAllowedRolesOf($this->store->getPermission('delete-company')));
    }

    /**
     * @covers ::getDirectlyAllowedRolesOf
     *
     * @test
     */
    public function it_returns_only_directly_allowed_roles_of_a_permission()
    {
        AccessControl::createManyRoles([
            ['name' => 'admin', 'title' => 'Admin'],
            ['name' => 'manager', 'title' => 'Manager'],
            ['name' => 'user', 'title' => 'User'],
        ]);

        AccessControl::createManyPermissions([
            ['name' => '*', 'title' => 'Do Anything'],
            ['name' => 'view-company', 'title' => 'View Company'],
            ['name' => 'edit-company', 'title' => 'Edit Company'],
            ['name' => 'delete-company', 'title' => 'Delete Company'],
        ]);

        AccessControl::allow('admin')->superPermission();
        AccessControl::allow('manager')->superPermission();
        AccessControl::forbid('manager')->to('delete-company');
        AccessControl::allow('user')->to('view-company');

        $this->assertCount(1, $this->store->getDirectlyAllowedRolesOf($this->store->getPermission('view-company')));
        $this->assertCount(0, $this->store->getDirectlyAllowedRolesOf($this->store->getPermission('edit-company')));
        $this->assertCount(0, $this->store->getDirectlyAllowedRolesOf($this->store->getPermission('delete-company')));
    }

    /**
     * @covers ::getIndirectlyAllowedRolesOf
     *
     * @test
     */
    public function it_returns_only_indirectly_allowed_roles_of_a_permission()
    {
        AccessControl::createManyRoles([
            ['name' => 'admin', 'title' => 'Admin'],
            ['name' => 'manager', 'title' => 'Manager'],
            ['name' => 'user', 'title' => 'User'],
        ]);

        AccessControl::createManyPermissions([
            ['name' => '*', 'title' => 'Do Anything'],
            ['name' => 'view-company', 'title' => 'View Company'],
            ['name' => 'edit-company', 'title' => 'Edit Company'],
            ['name' => 'delete-company', 'title' => 'Delete Company'],
        ]);

        AccessControl::allow('admin')->superPermission();
        AccessControl::allow('manager')->superPermission();
        AccessControl::forbid('manager')->to('delete-company');
        AccessControl::allow('user')->to('view-company');

        $this->assertCount(2, $this->store->getIndirectlyAllowedRolesOf($this->store->getPermission('view-company')));
        $this->assertCount(2, $this->store->getIndirectlyAllowedRolesOf($this->store->getPermission('edit-company')));
        $this->assertCount(1, $this->store->getIndirectlyAllowedRolesOf($this->store->getPermission('delete-company')));
    }

    /**
     * @covers ::getForbiddenRolesOf
     *
     * @test
     */
    public function it_returns_only_forbidden_roles_of_a_permission()
    {
        AccessControl::createManyRoles([
            ['name' => 'admin', 'title' => 'Admin'],
            ['name' => 'manager', 'title' => 'Manager'],
            ['name' => 'user', 'title' => 'User'],
        ]);

        AccessControl::createManyPermissions([
            ['name' => '*', 'title' => 'Do Anything'],
            ['name' => 'view-company', 'title' => 'View Company'],
            ['name' => 'edit-company', 'title' => 'Edit Company'],
            ['name' => 'delete-company', 'title' => 'Delete Company'],
        ]);

        AccessControl::allow('admin')->superPermission();
        AccessControl::allow('manager')->superPermission();
        AccessControl::forbid('manager')->to('delete-company');
        AccessControl::allow('user')->to('view-company');

        $this->assertCount(3, $this->store->getAllRolesOf($this->store->getPermission('view-company')));
        $this->assertCount(2, $this->store->getAllRolesOf($this->store->getPermission('edit-company')));
        $this->assertCount(2, $this->store->getAllRolesOf($this->store->getPermission('delete-company')));

        $this->assertCount(0, $this->store->getForbiddenRolesOf($this->store->getPermission('view-company')));
        $this->assertCount(0, $this->store->getForbiddenRolesOf($this->store->getPermission('edit-company')));
        $this->assertCount(1, $this->store->getForbiddenRolesOf($this->store->getPermission('delete-company')));
    }

    /**
     * @covers ::getDirectlyForbiddenRolesOf
     *
     * @test
     */
    public function it_returns_only_directly_forbidden_roles_of_a_permission()
    {
        AccessControl::createManyRoles([
            ['name' => 'admin', 'title' => 'Admin'],
            ['name' => 'manager', 'title' => 'Manager'],
            ['name' => 'user', 'title' => 'User'],
            ['name' => 'someone', 'title' => 'Someone'],
        ]);

        AccessControl::createManyPermissions([
            ['name' => '*', 'title' => 'Do Anything'],
            ['name' => 'view-company', 'title' => 'View Company'],
            ['name' => 'edit-company', 'title' => 'Edit Company'],
            ['name' => 'delete-company', 'title' => 'Delete Company'],
        ]);

        AccessControl::allow('admin')->superPermission();
        AccessControl::allow('manager')->superPermission();
        AccessControl::forbid('manager')->to('delete-company');
        AccessControl::forbid('someone')->superPermission();
        AccessControl::allow('someone')->to('view-company');
        AccessControl::allow('user')->to('view-company');

        $this->assertCount(4, $this->store->getAllRolesOf($this->store->getPermission('view-company')));
        $this->assertCount(3, $this->store->getAllRolesOf($this->store->getPermission('edit-company')));
        $this->assertCount(3, $this->store->getAllRolesOf($this->store->getPermission('delete-company')));

        $this->assertCount(0, $this->store->getDirectlyForbiddenRolesOf($this->store->getPermission('view-company')));
        $this->assertCount(0, $this->store->getDirectlyForbiddenRolesOf($this->store->getPermission('edit-company')));
        $this->assertCount(1, $this->store->getDirectlyForbiddenRolesOf($this->store->getPermission('delete-company')));
    }

    /**
     * @covers ::getIndirectlyForbiddenRolesOf
     *
     * @test
     */
    public function it_returns_only_indirectly_forbidden_roles_of_a_permission()
    {
        AccessControl::createManyRoles([
            ['name' => 'admin', 'title' => 'Admin'],
            ['name' => 'manager', 'title' => 'Manager'],
            ['name' => 'user', 'title' => 'User'],
            ['name' => 'someone', 'title' => 'Someone'],
        ]);

        AccessControl::createManyPermissions([
            ['name' => '*', 'title' => 'Do Anything'],
            ['name' => 'view-company', 'title' => 'View Company'],
            ['name' => 'edit-company', 'title' => 'Edit Company'],
            ['name' => 'delete-company', 'title' => 'Delete Company'],
        ]);

        AccessControl::allow('admin')->superPermission();
        AccessControl::allow('manager')->superPermission();
        AccessControl::forbid('manager')->to('delete-company');
        AccessControl::forbid('someone')->superPermission();
        AccessControl::allow('someone')->to('view-company');
        AccessControl::allow('user')->to('view-company');

        $this->assertCount(0, $this->store->getIndirectlyForbiddenRolesOf($this->store->getPermission('view-company')));
        $this->assertCount(1, $this->store->getIndirectlyForbiddenRolesOf($this->store->getPermission('edit-company')));
        $this->assertCount(1, $this->store->getIndirectlyForbiddenRolesOf($this->store->getPermission('delete-company')));
    }

    /**
     * @covers ::canRoleDo
     *
     * @test
     */
    public function it_checks_if_a_role_is_allowed_for_a_permission_directly_or_indirectly()
    {
        AccessControl::createManyRoles([
            ['name' => 'admin', 'title' => 'Admin'],
            ['name' => 'manager', 'title' => 'Manager'],
            ['name' => 'user', 'title' => 'User'],
            ['name' => 'someone', 'title' => 'Someone'],
        ]);

        AccessControl::createManyPermissions([
            ['name' => '*', 'title' => 'Do Anything'],
            ['name' => 'view-company', 'title' => 'View Company'],
            ['name' => 'edit-company', 'title' => 'Edit Company'],
            ['name' => 'delete-company', 'title' => 'Delete Company'],
        ]);

        AccessControl::allow('admin')->superPermission();
        AccessControl::allow('manager')->superPermission();
        AccessControl::forbid('manager')->to('delete-company');
        AccessControl::forbid('someone')->superPermission();
        AccessControl::allow('someone')->to('view-company');
        AccessControl::allow('user')->to('view-company');

        $this->assertTrue($this->store->canRoleDo($this->store->getRole('admin'), $this->store->getPermission('*')));
        $this->assertTrue($this->store->canRoleDo($this->store->getRole('admin'), $this->store->getPermission('view-company')));
        $this->assertTrue($this->store->canRoleDo($this->store->getRole('admin'), $this->store->getPermission('edit-company')));
        $this->assertTrue($this->store->canRoleDo($this->store->getRole('admin'), $this->store->getPermission('delete-company')));

        $this->assertTrue($this->store->canRoleDo($this->store->getRole('manager'), $this->store->getPermission('*')));
        $this->assertTrue($this->store->canRoleDo($this->store->getRole('manager'), $this->store->getPermission('view-company')));
        $this->assertTrue($this->store->canRoleDo($this->store->getRole('manager'), $this->store->getPermission('edit-company')));
        $this->assertFalse($this->store->canRoleDo($this->store->getRole('manager'), $this->store->getPermission('delete-company')));

        $this->assertFalse($this->store->canRoleDo($this->store->getRole('user'), $this->store->getPermission('*')));
        $this->assertTrue($this->store->canRoleDo($this->store->getRole('user'), $this->store->getPermission('view-company')));
        $this->assertFalse($this->store->canRoleDo($this->store->getRole('user'), $this->store->getPermission('edit-company')));
        $this->assertFalse($this->store->canRoleDo($this->store->getRole('user'), $this->store->getPermission('delete-company')));

        $this->assertFalse($this->store->canRoleDo($this->store->getRole('someone'), $this->store->getPermission('*')));
        $this->assertTrue($this->store->canRoleDo($this->store->getRole('someone'), $this->store->getPermission('view-company')));
        $this->assertFalse($this->store->canRoleDo($this->store->getRole('someone'), $this->store->getPermission('edit-company')));
        $this->assertFalse($this->store->canRoleDo($this->store->getRole('someone'), $this->store->getPermission('delete-company')));
    }
}
