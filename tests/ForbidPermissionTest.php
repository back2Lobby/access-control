<?php

namespace Back2Lobby\AccessControl\Tests;

use Back2Lobby\AccessControl\Exceptions\InvalidPermissionException;
use Back2Lobby\AccessControl\Facades\AccessControlFacade as AccessControl;
use Back2Lobby\AccessControl\Models\Permission;
use Back2Lobby\AccessControl\Models\Role;

/**
 * @coversDefaultClass \Back2Lobby\AccessControl\Service\ForbidPermission
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

        $this->assertCount(2, AccessControl::getStore()->getAllPermissionsOf($role));
        $this->assertCount(0, AccessControl::getStore()->getForbiddenPermissionsOf($role));

        AccessControl::forbid($role)->to($permission1);

        $this->assertCount(2, AccessControl::getStore()->getAllPermissionsOf($role));
        $this->assertCount(1, AccessControl::getStore()->getAllowedPermissionsOf($role));
        $this->assertCount(1, AccessControl::getStore()->getForbiddenPermissionsOf($role));
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

        AccessControl::allow($role1)->toDoEverything();
        AccessControl::allow($role2)->toDoEverything();
        AccessControl::allow($role2)->to($permission1);
        AccessControl::allow($role2)->to($permission2);

        $this->assertTrue(AccessControl::canRole($role1)->do($permission1));
        $this->assertTrue(AccessControl::canRole($role2)->do($permission1));

        $this->assertCount(3, AccessControl::getStore()->getAllPermissionsOf($role1));
        $this->assertCount(3, AccessControl::getStore()->getAllPermissionsOf($role2));
        $this->assertCount(3, AccessControl::getStore()->getAllowedPermissionsOf($role1));
        $this->assertCount(3, AccessControl::getStore()->getAllowedPermissionsOf($role2));
        $this->assertCount(0, AccessControl::getStore()->getForbiddenPermissionsOf($role1));
        $this->assertCount(0, AccessControl::getStore()->getForbiddenPermissionsOf($role2));

        AccessControl::forbid($role1)->toDoEverything();
        AccessControl::forbid($role2)->toDoEverything();

        $this->assertCount(3, AccessControl::getStore()->getAllPermissionsOf($role1));
        $this->assertCount(3, AccessControl::getStore()->getAllPermissionsOf($role2));
        $this->assertCount(0, AccessControl::getStore()->getAllowedPermissionsOf($role1));
        $this->assertCount(2, AccessControl::getStore()->getAllowedPermissionsOf($role2));
        $this->assertCount(3, AccessControl::getStore()->getForbiddenPermissionsOf($role1));
        $this->assertCount(1, AccessControl::getStore()->getForbiddenPermissionsOf($role2));
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

    /**
     * @covers ::toDoEverything
     *
     * @test
     */
    public function it_throws_exception_if_super_permission_is_not_found_in_database()
    {
        $role = Role::factory()->createFake();

        Permission::factory()->createFake();
        Permission::factory()->createFake();

        $this->expectException(InvalidPermissionException::class);
        AccessControl::disallow($role)->toDoEverything();
    }
}
