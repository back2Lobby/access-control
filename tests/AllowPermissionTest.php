<?php

namespace Back2Lobby\AccessControl\Tests;

use Back2Lobby\AccessControl\Facades\AccessControlFacade as AccessControl;
use Back2Lobby\AccessControl\Models\Permission;
use Back2Lobby\AccessControl\Models\Role;
use Illuminate\Support\Facades\DB;

/**
 * @coversDefaultClass \Back2Lobby\AccessControl\Services\AllowPermission
 */
class AllowPermissionTest extends BaseTestCase
{
    /**
     * @covers ::to
     *
     * @test
     */
    public function it_returns_true_if_permission_was_allowed_successfully_in_database()
    {
        $role = Role::factory()->createFake();

        $permission = Permission::factory()->createFake();

        $this->assertTrue(AccessControl::allow($role)->to($permission));

        $this->assertCount(1, DB::table('assigned_permissions')->get());
        $this->assertTrue(AccessControl::canRole($role)->do($permission));
    }

    /**
     * @covers ::to
     *
     * @test
     */
    public function it_syncs_access_store_after_allowing_permission()
    {
        $role = Role::factory()->createFake();

        $permission = Permission::factory()->createFake();

        $this->assertFalse(AccessControl::canRole($role)->do($permission));

        AccessControl::allow($role)->to($permission);

        $this->assertTrue(AccessControl::canRole($role)->do($permission));
        $this->assertCount(1, AccessControl::getMaps());
    }

    /**
     * @covers ::to
     *
     * @test
     */
    public function it_returns_false_if_permission_is_not_valid_or_not_found()
    {
        $role = Role::factory()->createFake();

        $this->assertFalse(AccessControl::allow($role)->to(999));
    }

    /**
     * @covers ::toDoEverything
     *
     * @test
     */
    public function it_allows_giving_super_permission_to_roles()
    {
        $role = Role::factory()->createFake();

        Permission::factory()->createFake([
            'name' => '*',
        ]);

        $permission1 = Permission::factory()->createFake();

        $this->assertTrue(AccessControl::allow($role)->superPermission());

        $this->assertTrue(AccessControl::canRole($role)->do($permission1));
    }
}
