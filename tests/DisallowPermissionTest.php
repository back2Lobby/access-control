<?php

namespace Back2Lobby\AccessControl\Tests;

use Back2Lobby\AccessControl\Facades\AccessControlFacade as AccessControl;
use Back2Lobby\AccessControl\Models\Permission;
use Back2Lobby\AccessControl\Models\Role;

/**
 * @coversDefaultClass \Back2Lobby\AccessControl\Services\DisallowPermission
 */
class DisallowPermissionTest extends BaseTestCase
{
    /**
     * @covers ::to
     *
     * @test
     */
    public function it_returns_true_if_permission_was_disallowed_successfully_in_database()
    {
        $role = Role::factory()->createFake();

        $permission1 = Permission::factory()->createFake();
        $permission2 = Permission::factory()->createFake();

        AccessControl::allow($role)->to($permission1);
        AccessControl::allow($role)->to($permission2);

        $this->assertCount(2, AccessControl::getAllPermissionsOf($role));

        AccessControl::disallow($role)->to($permission1);

        $this->assertCount(1, AccessControl::getAllPermissionsOf($role));
        $this->assertFalse(AccessControl::canRole($role)->do($permission1));
        $this->assertTrue(AccessControl::canRole($role)->do($permission2));
    }

    /**
     * @covers ::to
     *
     * @test
     */
    public function it_syncs_access_store_after_disallowing_permission()
    {
        $role = Role::factory()->createFake();

        $permission = Permission::factory()->createFake();

        AccessControl::allow($role)->to($permission);

        $this->assertTrue(AccessControl::canRole($role)->do($permission));

        AccessControl::disallow($role)->to($permission);

        $this->assertFalse(AccessControl::canRole($role)->do($permission));
    }

    /**
     * @covers ::to
     *
     * @test
     */
    public function it_returns_false_if_permission_is_not_valid_or_not_found()
    {
        $role = Role::factory()->createFake();

        $this->assertFalse(AccessControl::disallow($role)->to(999));
    }

    /**
     * @covers ::toDoEverything
     *
     * @test
     */
    public function it_allows_taking_back_super_permission_from_roles()
    {
        $role1 = Role::factory()->createFake();
        $role2 = Role::factory()->createFake();

        Permission::factory()->createFake([
            'name' => '*',
        ]);

        $permission1 = Permission::factory()->createFake();
        $permission2 = Permission::factory()->createFake();

        AccessControl::allow($role1)->superPermission();
        AccessControl::allow($role2)->superPermission();
        AccessControl::allow($role2)->to($permission1);
        AccessControl::allow($role2)->to($permission2);

        $this->assertTrue(AccessControl::canRole($role1)->do($permission1));
        $this->assertTrue(AccessControl::canRole($role2)->do($permission1));

        $this->assertCount(3, AccessControl::getAllPermissionsOf($role1));

        AccessControl::disallow($role1)->superPermission();

        $this->assertFalse(AccessControl::canRole($role1)->do($permission1));
        $this->assertTrue(AccessControl::canRole($role2)->do($permission1));
        $this->assertCount(0, AccessControl::getAllPermissionsOf($role1));
    }
}
