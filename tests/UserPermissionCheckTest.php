<?php

namespace Back2Lobby\AccessControl\Tests;

use Back2Lobby\AccessControl\Facades\AccessControlFacade as AccessControl;
use Back2Lobby\AccessControl\Models\Permission;
use Back2Lobby\AccessControl\Models\Role;
use Back2Lobby\AccessControl\Models\User;

/**
 * @coversDefaultClass \Back2Lobby\AccessControl\Service\UserPermissionCheck
 */
class UserPermissionCheckTest extends BaseTestCase
{
    /**
     * @covers ::do
     *
     * @test
     */
    public function it_returns_true_if_the_user_have_the_permission_directly()
    {
        $user = User::factory()->create();

        $role = Role::factory()->createFake();

        $permission1 = Permission::factory()->createFake();
        $permission2 = Permission::factory()->createFake();
        $permission3 = Permission::factory()->createFake();

        $role->forbid($permission1);
        $role->allow($permission2);

        $user->assign($role);

        $this->assertFalse(AccessControl::canUser($user)->do($permission1));
        $this->assertTrue(AccessControl::canUser($user)->do($permission2));
        $this->assertFalse(AccessControl::canUser($user)->do($permission3));
    }

    /**
     * @covers ::do
     *
     * @test
     */
    public function it_returns_true_if_the_user_have_the_permission_indirectly()
    {
        $user = User::factory()->create();

        $role = Role::factory()->createFake();

        $permission1 = Permission::factory()->createFake([
            'name' => '*',
        ]);
        $permission2 = Permission::factory()->createFake();
        $permission3 = Permission::factory()->createFake();

        $role->allow($permission1);
        $role->forbid($permission2);

        $user->assign($role);

        $this->assertTrue(AccessControl::canUser($user)->do($permission1));
        $this->assertFalse(AccessControl::canUser($user)->do($permission2));
        $this->assertTrue(AccessControl::canUser($user)->do($permission3));
    }

    /**
     * @covers ::do
     *
     * @test
     */
    public function it_returns_true_when_any_of_the_roles_of_user_have_the_permission_directly()
    {
        $user = User::factory()->create();

        $role1 = Role::factory()->createFake();
        $role2 = Role::factory()->createFake();
        $role3 = Role::factory()->createFake();

        $permission1 = Permission::factory()->createFake();
        $permission2 = Permission::factory()->createFake();
        $permission3 = Permission::factory()->createFake();
        $permission4 = Permission::factory()->createFake();

        $role1->allow($permission1);
        $role1->allow($permission3);
        $role2->allow($permission4);

        $user->assign($role1);
        $user->assign($role2);

        $this->assertTrue(AccessControl::canUser($user)->do($permission1));
        $this->assertFalse(AccessControl::canUser($user)->do($permission2));
        $this->assertTrue(AccessControl::canUser($user)->do($permission3));
        $this->assertTrue(AccessControl::canUser($user)->do($permission4));
    }

    /**
     * @covers ::do
     *
     * @test
     */
    public function it_returns_true_when_any_of_the_roles_of_user_have_the_permission_directly_or_indirectly()
    {
        $user = User::factory()->create();

        $role1 = Role::factory()->createFake();
        $role2 = Role::factory()->createFake();
        $role3 = Role::factory()->createFake();

        $permission1 = Permission::factory()->createFake();
        $permission2 = Permission::factory()->createFake([
            'name' => '*',
        ]);
        $permission3 = Permission::factory()->createFake();
        $permission4 = Permission::factory()->createFake();

        $role1->allow($permission2);
        $role2->forbid($permission4);
        $role3->forbid($permission3);

        $user->assign($role1);
        $user->assign($role2);

        $this->assertTrue(AccessControl::canUser($user)->do($permission1));
        $this->assertTrue(AccessControl::canUser($user)->do($permission2));
        $this->assertTrue(AccessControl::canUser($user)->do($permission3));
        $this->assertTrue(AccessControl::canUser($user)->do($permission4));
    }

    /**
     * @covers ::do
     *
     * @test
     */
    public function it_returns_true_if_permission_is_forbidden_in_one_role_and_allowed_in_other_roles_of_user()
    {
        $user = User::factory()->create();

        $role1 = Role::factory()->createFake();
        $role2 = Role::factory()->createFake();

        $permission1 = Permission::factory()->createFake();
        $permission2 = Permission::factory()->createFake();
        $permission3 = Permission::factory()->createFake();
        $permission4 = Permission::factory()->createFake();

        $role1->allow($permission1);
        $role1->allow($permission2);
        $role1->forbid($permission3);
        $role2->allow($permission3);
        $role2->forbid($permission4);

        $user->assign($role1);
        $user->assign($role2);

        $this->assertTrue(AccessControl::canUser($user)->do($permission1));
        $this->assertTrue(AccessControl::canUser($user)->do($permission2));
        $this->assertTrue(AccessControl::canUser($user)->do($permission3));
        $this->assertFalse(AccessControl::canUser($user)->do($permission4));
    }

    /**
     * @covers ::do
     *
     * @test
     */
    public function it_returns_false_if_permission_is_forbidden_in_one_role_and_is_not_allowed_in_other_roles_of_user()
    {
        $user = User::factory()->create();

        $role1 = Role::factory()->createFake();
        $role2 = Role::factory()->createFake();

        $permission1 = Permission::factory()->createFake();
        $permission2 = Permission::factory()->createFake();
        $permission3 = Permission::factory()->createFake();

        $role1->allow($permission1);
        $role1->allow($permission2);
        $role1->forbid($permission3);

        $user->assign($role1);
        $user->assign($role2);

        $this->assertTrue(AccessControl::canUser($user)->do($permission1));
        $this->assertTrue(AccessControl::canUser($user)->do($permission2));
        $this->assertFalse(AccessControl::canUser($user)->do($permission3));
    }

    /**
     * @covers ::do
     *
     * @test
     */
    public function it_returns_false_if_permissions_is_invalid_or_not_found()
    {
        $user = User::factory()->create();

        $role = Role::factory()->createFake();

        $permission = Permission::factory()->createFake();

        $role->allow($permission);

        $user->assign($role);

        $this->assertFalse(AccessControl::canUser($user)->do(999));
    }
}
