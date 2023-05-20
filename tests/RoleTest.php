<?php

namespace Back2Lobby\AccessControl\Tests;

use Back2Lobby\AccessControl\Exceptions\InvalidRoleableException;
use Back2Lobby\AccessControl\Facades\AccessControlFacade as AccessControl;
use Back2Lobby\AccessControl\Models\Permission;
use Back2Lobby\AccessControl\Models\Role;
use Back2Lobby\AccessControl\Tests\Models\Company;
use Back2Lobby\AccessControl\Tests\Models\Post;
use Back2Lobby\AccessControl\Tests\Models\User;
use Back2Lobby\AccessControl\Traits\SyncOnEvents;
use Illuminate\Database\QueryException;

/**
 * @coversDefaultClass \Back2Lobby\AccessControl\Models\Role
 * */
class RoleTest extends BaseTestCase
{

    /**
     * @covers \Back2Lobby\AccessControl\Models\Role
     *
     * @test
     */
    public function it_can_be_created()
    {
        Role::factory()->create();

        $this->assertEquals(1, Role::count());
    }

    /**
     * @covers ::allow
     *
     * @test
     */
    public function it_can_be_given_a_permission()
    {
        $role = Role::factory()->createFake();

        $permission = Permission::factory()->createFake();

        $this->assertTrue($role->allow($permission));

        $this->assertTrue(AccessControl::canRole($role)->do($permission));
        $this->assertCount(1, AccessControl::getPermissions());
        $this->assertCount(1, AccessControl::getRoles());
        $this->assertCount(1, AccessControl::getMaps());
    }

    /**
     * @covers ::allow
     *
     * @test
     */
    public function it_cannot_have_same_permission_twice()
    {
        $role = Role::factory()->createFake();

        $permission = Permission::factory()->createFake();

        // giving permission for first time
        $this->assertTrue($role->allow($permission));

        // giving same permission for second time
        $this->assertTrue($role->allow($permission));

        $this->assertCount(1, AccessControl::getAllPermissionsOf($role));
        $this->assertCount(1, AccessControl::getAllowedPermissionsOf($role));
        $this->assertCount(1, AccessControl::getMaps());
    }

    /**
     * @covers ::disallow
     *
     * @test
     */
    public function it_can_be_disallowed_for_a_permission()
    {
        $role = Role::factory()->createFake();

        $permission1 = Permission::factory()->createFake();
        $permission2 = Permission::factory()->createFake();

        $this->assertTrue($role->allow($permission1));
        $this->assertTrue($role->allow($permission2));

        $this->assertTrue($role->disallow($permission1));

        $this->assertFalse(AccessControl::canRole($role)->do($permission1));
        $this->assertTrue(AccessControl::canRole($role)->do($permission2));
        $this->assertCount(2, AccessControl::getPermissions());
        $this->assertCount(1, AccessControl::getAllowedPermissionsOf($role));
    }

    /**
     * @covers ::forbid
     *
     * @test
     */
    public function it_can_be_set_forbidden_for_a_permission()
    {
        $role = Role::factory()->createFake();

        $permission1 = Permission::factory()->createFake();
        $permission2 = Permission::factory()->createFake();

        $this->assertTrue($role->allow($permission1));
        $this->assertTrue($role->forbid($permission2));

        $this->assertCount(2, AccessControl::getAllPermissionsOf($role));
        $this->assertCount(1, AccessControl::getAllowedPermissionsOf($role));
        $this->assertCount(1, AccessControl::getForbiddenPermissionsOf($role));
    }

    /**
     * @covers ::booted
     *
     * @test
     */
    public function it_syncs_role_on_creation()
    {
        $role = Role::factory()->createFake();

        $roles = AccessControl::getRoles();

        $this->assertCount(1, $roles);
        $this->assertIsInt($role->id);
        $this->assertEquals($role->id, $roles->first()->id);
    }

//    /**
//     * @covers ::booted
//     *
//     * @test
//     */
//    public function it_syncs_role_on_deletion()
//    {
//        $role = Role::factory()->createFake();
//
//        $roles = AccessControl::getRoles();
//
//        $this->assertCount(1, $roles);
//
//        // now deleting
//        $role->delete();
//
//        $roles = AccessControl::getRoles();
//        $this->assertCount(0, $roles);
//    }

//    /**
//     * @covers ::booted
//     *
//     * @test
//     */
//    public function it_syncs_role_on_save()
//    {
//        $role = new Role([
//            'name' => 'admin',
//            'title' => 'Admin',
//        ]);
//
//        $role->save();
//
//        $roles = AccessControl::getRoles();
//
//        $this->assertCount(1, $roles);
//        $this->assertIsInt($role->id);
//        $this->assertEquals($role->id, $roles->first()->id);
//        $this->assertSame($role->title, $roles->first()->title);
//    }

//    /**
//     * @covers ::booted
//     *
//     * @test
//     */
//    public function it_syncs_role_on_update()
//    {
//        $role = Role::factory()->createFake();
//
//        $roles = AccessControl::getRoles();
//
//        $this->assertCount(1, $roles);
//
//        // updating
//        $role->update([
//            'title' => 'Test Title',
//        ]);
//
//        $roles = AccessControl::getRoles();
//
//        $this->assertCount(1, $roles);
//        $this->assertIsInt($role->id);
//        $this->assertEquals($role->id, $roles->first()->id);
//        $this->assertSame($role->title, $roles->first()->title);
//    }

    /**
     * @covers ::permissions
     *
     * @test
     */
    public function it_can_have_many_permissions()
    {
        $role = Role::factory()->createFake();

        $permission1 = Permission::factory()->createFake();
        $permission2 = Permission::factory()->createFake();
        $permission3 = Permission::factory()->createFake();

        $role->allow($permission1);
        $role->allow($permission2);
        $role->allow($permission3);

        $this->assertTrue(AccessControl::canRole($role)->do($permission1));
        $this->assertTrue(AccessControl::canRole($role)->do($permission2));
        $this->assertTrue(AccessControl::canRole($role)->do($permission3));

        $this->assertCount(3, AccessControl::getPermissions());
        $this->assertCount(1, AccessControl::getRoles());
        $this->assertCount(3, AccessControl::getMaps());
    }

    /**
     * @covers ::users
     *
     * @test
     */
    public function it_can_have_many_users()
    {
        $role = Role::factory()->createFake();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        AccessControl::assign($role)->to($user1);
        AccessControl::assign($role)->to($user2);
        AccessControl::assign($role)->to($user3);

        $this->assertEquals(3, User::whereIs($role)->count());
    }

    /**
     * @coversNothing
     *
     * @test
     */
    public function it_can_have_many_roleables_in_the_column()
    {
        $role = Role::factory()->createFake([
            'roleables' => [
                Company::class,
                Post::class,
            ],
        ]);

        $this->assertIsArray($role->roleables);
        $this->assertCount(2, $role->roleables);
        $this->assertTrue(in_array(Company::class, $role->roleables));
        $this->assertTrue(in_array(Post::class, $role->roleables));
    }

    /**
     * @coversNothing
     *
     * @test
     */
    public function it_casts_roleables_json_column_to_array()
    {
        $role1 = Role::factory()->createFake([
            'roleables' => [
                Company::class,
                Post::class,
            ],
        ]);

        $this->assertIsArray($role1->roleables);

        $role2 = Role::make([
            'name' => 'editor',
            'title' => 'Editor',
        ]);

        $role2->roleables = [Post::class];

        $role2->save();

        $this->assertIsArray($role2->roleables);
        $this->assertSame(Post::class, $role2->roleables[0]);
    }

    /**
     * @coversNothing
     *
     * @test
     */
    public function it_can_have_only_a_unique_name()
    {
        $role1 = new Role([
            'name' => 'admin',
            'title' => 'Admin',
        ]);

        $role1->save();

        $role2 = new Role([
            'name' => 'admin',
            'title' => 'Admin',
        ]);

        $this->expectException(QueryException::class);
        $this->expectExceptionCode(23000);
        $role2->save();
    }

    /**
     * @covers ::getValidRoleable
     *
     * @test
     */
    public function it_gives_valid_roleable()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $role1 = Role::factory()->createFake();
        $role2 = Role::factory()->createFake([
            'roleables' => [
                Post::class,
            ],
        ]);
        $role3 = Role::factory()->createFake([
            'roleables' => [
                Company::class,
            ],
        ]);

        $post1 = Post::factory()->create();
        $company1 = Company::factory()->create();

        $user1->assign($role1);
        $user1->assign($role2, $post1);
        $user2->assign($role2, $post1);

        $this->assertCount(1, User::whereIs($role1)->get());
        $this->assertSame($user1->id, User::whereIs($role1)->first()->id);

        $this->assertCount(2, User::whereIs($role2, $post1)->get());
        $this->assertSameArray([$user1->id, $user2->id], User::whereIs($role2, $post1)->get()->pluck('id'));

        $this->assertCount(0, User::whereIs($role3, $company1)->get());
    }

    /**
     * @covers ::getValidRoleable
     *
     * @test
     */
    public function it_throws_exception_if_a_roleable_is_given_for_role_with_no_roleables()
    {
        $role1 = Role::factory()->createFake();

        $post1 = Post::factory()->create();

        $this->expectException(InvalidRoleableException::class);
        User::whereIs($role1, $post1)->get();
    }

    /**
     * @covers ::getValidRoleable
     *
     * @test
     */
    public function it_throws_exception_if_invalid_roleable_is_given_for_role_with_roleables()
    {
        $role1 = Role::factory()->createFake([
            'roleables' => [Post::class],
        ]);

        $company1 = Company::factory()->create();

        $this->expectException(InvalidRoleableException::class);
        User::whereIs($role1, $company1)->get();
    }
}
