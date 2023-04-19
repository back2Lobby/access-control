<?php

namespace Back2Lobby\AccessControl\Tests;

use Back2Lobby\AccessControl\Facades\AccessControlFacade as AccessControl;
use Back2Lobby\AccessControl\Models\Permission;
use Back2Lobby\AccessControl\Models\Role;

/**
 * @coversDefaultClass \Back2Lobby\AccessControl\Service\RolePermissionCheck
 */
class RolePermissionCheckTest extends BaseTestCase
{
    /**
     * @covers ::do
     *
     * @test
     */
    public function it_returns_true_if_permission_is_allowed_for_role_directly()
    {
        $role = Role::factory()->createFake();

        $permission1 = Permission::factory()->createFake();
        $permission2 = Permission::factory()->createFake();
        $permission3 = Permission::factory()->createFake();

        AccessControl::allow($role)->to($permission1);
        AccessControl::forbid($role)->to($permission2);

        $this->assertTrue(AccessControl::canRole($role)->do($permission1));
        $this->assertFalse(AccessControl::canRole($role)->do($permission2));
        $this->assertFalse(AccessControl::canRole($role)->do($permission3));
    }

    /**
     * @covers ::do
     *
     * @test
     */
    public function it_returns_false_if_permission_is_forbidden_for_role_directly()
    {
        $role = Role::factory()->createFake();

        $permission = Permission::factory()->createFake();

        AccessControl::forbid($role)->to($permission);

        $this->assertFalse(AccessControl::canRole($role)->do($permission));
    }

    /**
     * @covers ::do
     *
     * @test
     */
    public function it_returns_true_if_permission_is_allowed_for_role_indirectly()
    {
        $role = Role::factory()->createFake();

        $permission1 = Permission::factory()->createFake([
            'name' => '*',
        ]);
        $permission2 = Permission::factory()->createFake();
        $permission3 = Permission::factory()->createFake();
        $permission4 = Permission::factory()->createFake();

        AccessControl::allow($role)->to($permission1);
        AccessControl::forbid($role)->to($permission2);

        $this->assertTrue(AccessControl::canRole($role)->do($permission1));
        $this->assertFalse(AccessControl::canRole($role)->do($permission2));
        $this->assertTrue(AccessControl::canRole($role)->do($permission3));
        $this->assertTrue(AccessControl::canRole($role)->do($permission4));
    }

    /**
     * @covers ::do
     *
     * @test
     */
    public function it_returns_false_if_permission_is_forbidden_for_role_indirectly()
    {
        $role = Role::factory()->createFake();

        $permission1 = Permission::factory()->createFake([
            'name' => '*',
        ]);
        $permission2 = Permission::factory()->createFake();
        $permission3 = Permission::factory()->createFake();

        AccessControl::allow($role)->to($permission2);
        AccessControl::forbid($role)->toDoEverything();

        $this->assertFalse(AccessControl::canRole($role)->do($permission1));
        $this->assertTrue(AccessControl::canRole($role)->do($permission2));
        $this->assertFalse(AccessControl::canRole($role)->do($permission3));
    }

    /**
     * @covers ::do
     *
     * @test
     */
    public function it_returns_false_if_permission_is_invalid_or_not_found()
    {
        $role = Role::factory()->createFake();

        $this->assertFalse(AccessControl::canRole($role)->do(999));
    }
}
