<?php

namespace Back2Lobby\AccessControl\Tests;

use Back2Lobby\AccessControl\Exceptions\InvalidPermissionException;
use Back2Lobby\AccessControl\Exceptions\InvalidRoleableException;
use Back2Lobby\AccessControl\Exceptions\InvalidUserException;
use Back2Lobby\AccessControl\Facades\AccessControlFacade as AccessControl;
use Back2Lobby\AccessControl\Models\Permission;
use Back2Lobby\AccessControl\Models\Role;
use Back2Lobby\AccessControl\Models\User;
use Back2Lobby\AccessControl\Tests\Models\Company;
use Back2Lobby\AccessControl\Tests\Models\Post;
use Illuminate\Support\Collection;

/**
 * @coversDefaultClass \Back2Lobby\AccessControl\Models\User
 * */
class UserTest extends BaseTestCase
{
    /**
     * @covers ::roles
     *
     * @test
     */
    public function it_can_have_many_roles()
    {
        $user = User::factory()->create();

        $role1 = Role::factory()->createFake();
        $role2 = Role::factory()->createFake(['roleables' => [Company::class]]);
        $role3 = Role::factory()->createFake(['roleables' => [Post::class, Company::class]]);

        AccessControl::assign($role1)->to($user);
        AccessControl::assign($role2, Company::factory()->create())->to($user);
        AccessControl::assign($role3, Post::factory()->create())->to($user);

        $this->assertEquals(3, AccessControl::getStore()->getRoles()->count());
        $userRoles = $user->roles()->get()->pluck('id');

        $this->assertInstanceOf(Collection::class, $userRoles);

        $this->assertEquals(3, $userRoles->count());
        $this->assertSameArray($userRoles, [$role1->id, $role2->id, $role3->id]);
    }

    /**
     * @covers ::permissions
     *
     * @test
     */
    public function it_can_have_many_permissions_through_roles()
    {
        $user = User::factory()->createOne();

        // making sure user doesn't have any permission already
        $userPermissions = $user->permissions();
        $this->assertInstanceOf(Collection::class, $userPermissions);
        $this->assertEquals(0, $userPermissions->count());

        $role1 = Role::factory()->createFake();
        $role2 = Role::factory()->createFake(['roleables' => [Post::class]]);

        $permission1 = Permission::factory()->createFake();
        $permission2 = Permission::factory()->createFake();
        $permission3 = Permission::factory()->createFake();

        AccessControl::allow($role1)->to($permission1);
        AccessControl::allow($role1)->to($permission2);
        AccessControl::allow($role2)->to($permission3);

        AccessControl::assign($role1)->to($user);
        AccessControl::assign($role2, Post::factory()->create())->to($user);

        // now checking permissions
        $userPermissions = $user->permissions();
        $this->assertInstanceOf(Collection::class, $userPermissions);
        $this->assertEquals(3, $userPermissions->count());
        $this->assertSameArray($userPermissions->pluck('id'), [$permission1->id, $permission2->id, $permission3->id]);
    }

    /**
     * @covers ::permissions
     *
     * @test
     */
    public function it_returns_only_allowed_permissions()
    {
        $user = User::factory()->createOne();

        $role1 = Role::factory()->createFake();
        $role2 = Role::factory()->createFake(['roleables' => [Post::class]]);

        $permission1 = Permission::factory()->createFake();
        $permission2 = Permission::factory()->createFake();
        $permission3 = Permission::factory()->createFake();

        AccessControl::allow($role1)->to($permission1);
        AccessControl::allow($role1)->to($permission2);
        AccessControl::forbid($role2)->to($permission3);

        $user->assign($role1);
        $user->assign($role2, Post::factory()->create());

        $this->assertCount(2, $user->permissions());
        $this->assertSameArray([$permission1->id, $permission2->id], $user->permissions()->pluck('id'));
    }

    /**
     * @covers ::assign
     *
     * @test
     */
    public function it_can_be_assigned_role_without_roleable_using_assign_func()
    {
        $user = User::factory()->create();

        $role = Role::factory()->createFake();

        $this->assertEquals(0, $user->roles()->count());

        $user->assign($role);

        $this->assertEquals(1, $user->roles()->count());
    }

    /**
     * @covers ::assign
     *
     * @test
     */
    public function it_can_be_assigned_role_with_roleable_using_assign_func()
    {
        $user = User::factory()->create();

        $role = Role::factory()->createFake([
            'roleables' => [Company::class],
        ]);

        $roleable = Company::factory()->create();

        $user->assign($role, $roleable);

        $this->assertCount(1, AccessControl::getStore()->getRoles());
        $this->assertTrue(User::whereIs($role, $roleable)->exists());
    }

    /**
     * @covers ::assign
     *
     * @test
     */
    public function it_returns_false_when_it_could_not_assign_role_using_assign_func()
    {
        $user = User::factory()->create();

        $role = Role::factory()->createFake();

        $this->assertEquals(0, $user->roles()->count());

        $this->assertTrue($user->assign($role));

        $this->assertEquals(1, $user->roles()->count());

        // assigning same role again
        $this->assertFalse($user->assign($role));
        $this->assertEquals(1, $user->roles()->count());
    }

    /**
     * @covers ::whereIs
     *
     * @test
     */
    public function it_allows_getting_users_with_specific_role_and_roleable()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $role1 = Role::factory()->createFake();
        $role2 = Role::factory()->createFake([
            'roleables' => [Post::class],
        ]);
        $role3 = Role::factory()->create([
            'roleables' => [Company::class, Post::class],
        ]);

        $post1 = Post::factory()->create();
        $post2 = Post::factory()->create();
        $company1 = Company::factory()->create();

        $user1->assign($role1);
        $user1->assign($role2, $post1);
        $user2->assign($role2, $post1);
        $user2->assign($role3, $post2);
        $user2->assign($role3, $company1);

        $this->assertCount(1, User::whereIs($role1)->get());
        $this->assertSame($user1->id, User::whereIs($role1)->first()->id);

        $this->assertCount(2, User::whereIs($role2, $post1)->get());
        $this->assertSameArray(User::whereIs($role2, $post1)->pluck('id'), [$user1->id, $user2->id]);

        $this->assertCount(1, User::whereIs($role3, $post2)->get());
        $this->assertSame($user2->id, User::whereIs($role3, $post2)->first()->id);

        $this->assertCount(1, User::whereIs($role3, $company1)->get());
        $this->assertSame($user2->id, User::whereIs($role3, $company1)->first()->id);
    }

    /**
     * @covers ::whereIs
     *
     * @test
     * */
    public function it_throws_exception_when_a_roleable_is_given_for_role_with_no_roleables_while_getting_users()
    {
        $user1 = User::factory()->create();

        $role1 = Role::factory()->createFake();

        $post1 = Post::factory()->create();

        $user1->assign($role1);

        $this->expectException(InvalidRoleableException::class);
        User::whereIs($role1, $post1)->get();
    }

    /**
     * @covers ::whereIs
     *
     * @test
     * */
    public function it_throws_exception_when_no_roleable_is_given_for_role_with_roleables_while_getting_users()
    {
        $user1 = User::factory()->create();

        $role1 = Role::factory()->createFake([
            'roleables' => [Post::class],
        ]);

        $post1 = Post::factory()->create();

        $user1->assign($role1, $post1);

        $this->expectException(InvalidRoleableException::class);
        User::whereIs($role1)->get();
    }

    /**
     * @covers ::whereIs
     *
     * @test
     * */
    public function it_throws_exception_when_invalid_roleable_is_given_for_role_with_roleables_while_getting_users()
    {
        $user1 = User::factory()->create();

        $role1 = Role::factory()->createFake([
            'roleables' => [Post::class],
        ]);

        $post1 = Post::factory()->create();
        $company1 = Company::factory()->create();

        $user1->assign($role1, $post1);

        $this->expectException(InvalidRoleableException::class);
        User::whereIs($role1, $company1)->get();
    }

    /**
     * @covers ::whereCan
     *
     * @test
     */
    public function it_can_get_users_based_on_permissions()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $role1 = Role::factory()->createFake();
        $role2 = Role::factory()->createFake(
            ['roleables' => [Post::class]]
        );
        $role3 = Role::factory()->createFake();

        $permission1 = Permission::factory()->createFake();
        $permission2 = Permission::factory()->createFake();
        $permission3 = Permission::factory()->createFake();
        $permission4 = Permission::factory()->createFake();

        $post1 = Post::factory()->create();
        $post2 = Post::factory()->create();

        $role1->allow($permission1);
        $role1->allow($permission2);
        $role2->allow($permission3);
        $role3->allow($permission2);
        $role3->allow($permission4);

        $user1->assign($role1);
        $user2->assign($role1);
        $user2->assign($role2, $post2);
        $user3->assign($role2, $post1);
        $user3->assign($role3);

        $this->assertCount(2, User::whereCan($permission1)->get());
        $this->assertSameArray([$user1->id, $user2->id], User::whereCan($permission1)->get()->pluck('id'));

        $this->assertCount(3, User::whereCan($permission2)->get());
        $this->assertSameArray([$user1->id, $user2->id, $user3->id], User::whereCan($permission2)->get()->pluck('id'));

        $this->assertCount(1, User::whereCan($permission3, $post2)->get());
        $this->assertSame($user2->id, User::whereCan($permission3, $post2)->first()->id);

        $this->assertCount(1, User::whereCan($permission3, $post1)->get());
        $this->assertSame($user3->id, User::whereCan($permission3, $post1)->first()->id);

        $this->assertCount(1, User::whereCan($permission4)->get());
        $this->assertSame($user3->id, User::whereCan($permission4)->first()->id);
    }

    /**
     * @covers ::whereCan
     *
     * @test
     */
    public function it_allows_getting_users_having_permissions_indirectly()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $role1 = Role::factory()->createFake();
        $role2 = Role::factory()->createFake();

        $superPermission = Permission::factory()->createFake([
            'name' => '*',
        ]);
        $permission1 = Permission::factory()->createFake();
        $permission2 = Permission::factory()->createFake();

        $role1->allow($superPermission);
        $role1->forbid($permission1);

        $role2->allow($permission1);
        $role2->allow($permission2);

        $user1->assign($role1);
        $user2->assign($role2);

        $this->assertCount(1, User::whereCan($superPermission)->get());
        $this->assertSame($user1->id, User::whereCan($superPermission)->first()->id);

        $this->assertCount(1, User::whereCan($permission1)->get());
        $this->assertSame($user2->id, User::whereCan($permission1)->first()->id);

        $this->assertCount(1, User::whereCan($permission2)->get());
        $this->assertSame($user2->id, User::whereCan($permission2)->first()->id);

        $this->assertCount(2, User::whereCan($permission2, includeIndirectRoles: true)->get());
        $this->assertSameArray([$user1->id, $user2->id], User::whereCan($permission2, includeIndirectRoles: true)->get()->pluck('id'));
    }

    /**
     * @covers ::whereCan
     *
     * @test
     */
    public function it_throws_exception_if_permission_is_not_found_or_is_invalid()
    {
        $this->expectException(InvalidPermissionException::class);
        User::whereCan(999)->get();
    }

    /**
     * @covers ::getValidUser
     *
     * @test
     */
    public function it_gives_valid_user()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->assertSame($user1->id, User::getValidUser($user1)->id);
        $this->assertSame($user1->id, User::getValidUser($user1->id)->id);

        $this->assertSame($user2->id, User::getValidUser($user2)->id);
        $this->assertSame($user2->id, User::getValidUser($user2->id)->id);
    }

    /**
     * @covers ::getValidUser
     *
     * @test
     */
    public function it_throws_exception_if_passed_user_is_not_found_in_database()
    {
        User::factory()->create();
        User::factory()->create();

        $this->expectException(InvalidUserException::class);
        User::getValidUser(999);
    }
}
