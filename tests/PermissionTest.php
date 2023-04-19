<?php

namespace Back2Lobby\AccessControl\Tests;

use Back2Lobby\AccessControl\Facades\AccessControlFacade as AccessControl;
use Back2Lobby\AccessControl\Models\Permission;
use Back2Lobby\AccessControl\Models\Role;
use Illuminate\Database\QueryException;

/**
 * @covers \Back2Lobby\AccessControl\Models\Permission
 * */
class PermissionTest extends BaseTestCase
{
    /** @test */
    public function it_uses_sync_on_events_trait()
    {
        $this->assertTrue(in_array('Back2Lobby\\AccessControl\\Traits\\syncOnEvents', class_uses(Permission::class)));
    }

    /** @test */
    public function it_can_create_permission()
    {
        Permission::factory()->create();

        $this->assertEquals(1, Permission::count());
    }

    /** @test */
    public function it_syncs_permission_on_creation()
    {
        $permission = Permission::factory()->createFake();

        $permissions = AccessControl::getStore()->getPermissions();

        $this->assertCount(1, $permissions);
        $this->assertIsInt($permission->id);
        $this->assertEquals($permission->id, $permissions->first()->id);
    }

    /** @test */
    public function it_syncs_permission_on_deletion()
    {
        $permission = Permission::factory()->createFake();

        $permissions = AccessControl::getStore()->getPermissions();

        $this->assertCount(1, $permissions);

        // now deleting
        $permission->delete();

        $permissions = AccessControl::getStore()->getPermissions();
        $this->assertCount(0, $permissions);
    }

    /** @test */
    public function it_syncs_permission_on_save()
    {
        $permission = new Permission([
            'name' => 'edit-post',
            'title' => 'Edit Post',
        ]);

        $permission->save();

        $permissions = AccessControl::getStore()->getPermissions();

        $this->assertCount(1, $permissions);
        $this->assertIsInt($permission->id);
        $this->assertEquals($permission->id, $permissions->first()->id);
        $this->assertSame($permission->title, $permissions->first()->title);
    }

    /** @test */
    public function it_syncs_permission_on_update()
    {
        $permission = Permission::factory()->createFake();

        $permissions = AccessControl::getStore()->getPermissions();

        $this->assertCount(1, $permissions);

        // updating
        $permission->update([
            'title' => 'Test Title',
        ]);

        $permissions = AccessControl::getStore()->getPermissions();

        $this->assertCount(1, $permissions);
        $this->assertIsInt($permission->id);
        $this->assertEquals($permission->id, $permissions->first()->id);
        $this->assertSame($permission->title, $permissions->first()->title);
    }

    /** @test */
    public function it_can_have_many_roles()
    {
        $permission = Permission::factory()->createFake();

        // make sure it doesn't have any roles yet
        $this->assertEquals(0, AccessControl::getStore()->getAllRolesOf($permission)->count());

        $role1 = Role::factory()->createFake();
        $role2 = Role::factory()->createFake();
        $role3 = Role::factory()->createFake();

        AccessControl::allow($role1)->to($permission);
        AccessControl::allow($role2)->to($permission);
        AccessControl::allow($role3)->to($permission);

        $this->assertCount(3, AccessControl::getStore()->getAllRolesOf($permission));
    }

    /**
     * @coversNothing
     *
     * @test
     */
    public function it_can_have_only_a_unique_name()
    {
        $permission1 = new Permission([
            'name' => 'edit-company',
            'title' => 'Edit Company',
        ]);

        $permission1->save();

        $permission2 = new Permission([
            'name' => 'edit-company',
            'title' => 'Edit Company',
        ]);

        $this->expectException(QueryException::class);
        $this->expectExceptionCode(23000);
        $permission2->save();
    }
}
