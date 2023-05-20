<?php

namespace Back2Lobby\AccessControl\Tests;

use Back2Lobby\AccessControl\Facades\AccessControlFacade as AccessControl;
use Back2Lobby\AccessControl\Models\Permission;
use Back2Lobby\AccessControl\Models\Role;

/**
 * @coversDefaultClass \Back2Lobby\AccessControl\Services\ForbidPermission
 */
class ForbidPermissionTest extends BaseTestCase
{
    /**
     * @covers ::to
     *
     * @test
     */
    public function it_returns_true_if_permission_was_forbidding_successfully_in_database()
    {
        $role = Role::factory()->createFake();

        $permission1 = Permission::factory()->createFake();
        $permission2 = Permission::factory()->createFake();

        AccessControl::allow($role)->to($permission1);
        AccessControl::allow($role)->to($permission2);

        $this->assertCount(2, AccessControl::getAllPermissionsOf($role));
        $this->assertCount(0, AccessControl::getForbiddenPermissionsOf($role));

        AccessControl::forbid($role)->to($permission1);

        $this->assertCount(2, AccessControl::getAllPermissionsOf($role));
        $this->assertCount(1, AccessControl::getAllowedPermissionsOf($role));
        $this->assertCount(1, AccessControl::getForbiddenPermissionsOf($role));
        $this->assertFalse(AccessControl::canRole($role)->do($permission1));
        $this->assertTrue(AccessControl::canRole($role)->do($permission2));
    }

    /**
     * @covers ::to
     *
     * @test
     */
    public function it_forbids_super_permission_to_roles()
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
        $this->assertCount(3, AccessControl::getAllPermissionsOf($role2));
        $this->assertCount(3, AccessControl::getAllowedPermissionsOf($role1));
        $this->assertCount(3, AccessControl::getAllowedPermissionsOf($role2));
        $this->assertCount(0, AccessControl::getForbiddenPermissionsOf($role1));
        $this->assertCount(0, AccessControl::getForbiddenPermissionsOf($role2));

        AccessControl::forbid($role1)->superPermission();
        AccessControl::forbid($role2)->superPermission();

        $this->assertCount(3, AccessControl::getAllPermissionsOf($role1));
        $this->assertCount(3, AccessControl::getAllPermissionsOf($role2));
        $this->assertCount(0, AccessControl::getAllowedPermissionsOf($role1));
        $this->assertCount(2, AccessControl::getAllowedPermissionsOf($role2));
        $this->assertCount(3, AccessControl::getForbiddenPermissionsOf($role1));
        $this->assertCount(1, AccessControl::getForbiddenPermissionsOf($role2));
    }

    /**
     * @covers ::to
     *
     * @test
     */
    public function it_returns_false_if_permission_is_not_valid_or_not_found()
    {
        $role = Role::factory()->createFake();

        $this->assertFalse(AccessControl::forbid($role)->to(999));
    }
}
