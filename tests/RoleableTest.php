<?php

namespace Back2Lobby\AccessControl\Tests;

use Back2Lobby\AccessControl\Facades\AccessControlFacade as AccessControl;
use Back2Lobby\AccessControl\Models\Permission;
use Back2Lobby\AccessControl\Models\Role;
use Back2Lobby\AccessControl\Tests\Models\Post;
use Back2Lobby\AccessControl\Tests\Models\User;
use Back2Lobby\AccessControl\Traits\Roleable;

class RoleableTest extends BaseTestCase
{
    /**
     * @covers Roleable::usersHavingPermission
     *
     * @test
     */
    public function it_returns_all_the_users_with_specific_permission_on_given_roleable()
    {
        $role1 = Role::factory()->createFake([
            'roleables' => [Post::class],
        ]);
        $role2 = Role::factory()->createFake([
            'roleables' => [Post::class],
        ]);
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $permission1 = Permission::factory()->createFake();
        $permission2 = Permission::factory()->createFake();

        $role1->allow($permission1);
        $role1->allow($permission2);

        $role2->allow($permission2);

        $post = Post::factory()->create();

        AccessControl::assign($role1, $post)->to($user1);
        AccessControl::assign($role2, $post)->to($user2);

        $users = $post->usersHavingPermission($permission1->name)->get();

        $this->assertCount(1, $users);
        $this->assertSame($user1->id, $users->first()?->id);

        $users = $post->usersHavingPermission($permission2->name)->get();

        $this->assertCount(2, $users);
        $this->assertSameArray([$user1->id, $user2->id], $users->pluck('id'));
    }

    /**
     * @covers Roleable::users
     *
     * @test
     */
    public function it_returns_all_the_users_having_any_role_on_this_roleable()
    {
        $role = Role::factory()->createFake([
            'roleables' => [Post::class],
        ]);

        $user = User::factory()->create();

        $permission1 = Permission::factory()->createFake();
        $permission2 = Permission::factory()->createFake();

        $role->allow($permission1);
        $role->allow($permission2);

        $post = Post::factory()->create();

        $this->assertCount(0, $post->users()->get());

        AccessControl::assign($role, $post)->to($user);

        $this->assertCount(1, $post->users()->get());
    }

    /**
     * @covers Roleable::usersWithRoles
     *
     * @test
     */
    public function it_returns_all_the_users_of_a_roleable_along_with_the_roles()
    {
        $role1 = Role::factory()->createFake([
            'roleables' => [Post::class],
        ]);
        $role2 = Role::factory()->createFake();

        $user = User::factory()->create();

        $permission1 = Permission::factory()->createFake();
        $permission2 = Permission::factory()->createFake();

        $role1->allow($permission1);
        $role1->allow($permission2);
        $role2->allow($permission2);

        $post = Post::factory()->create();

        $this->assertCount(0, $post->usersWithRoles());

        AccessControl::assign($role1, $post)->to($user);
        AccessControl::assign($role2)->to($user);

        $this->assertCount(1, $post->usersWithRoles());
        $this->assertCount(1, $post->usersWithRoles()->first()->roles);
        $this->assertSame($role1->name, $post->usersWithRoles()->first()->roles->first()->name);
    }

    /**
     * @covers Roleable::whereUserCan
     *
     * @test
     */
    public function it_returns_all_the_roleable_for_the_user_with_given_permission()
    {
        $role1 = Role::factory()->createFake([
            'roleables' => [Post::class],
        ]);
        $role2 = Role::factory()->createFake();

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $permission1 = Permission::factory()->createFake();
        $permission2 = Permission::factory()->createFake();

        $role1->allow($permission1);
        $role1->allow($permission2);
        $role2->allow($permission2);

        $post1 = Post::factory()->create();
        $post2 = Post::factory()->create();

        $this->assertCount(0, Post::whereUserCan($user1, $permission1)->get());
        $this->assertCount(0, Post::whereUserCan($user2, $permission2)->get());
        AccessControl::assign($role1, $post1)->to($user1);
        AccessControl::assign($role1, $post1)->to($user2);
        AccessControl::assign($role1, $post2)->to($user2);

        $this->assertCount(1, Post::whereUserCan($user1, $permission1)->get());
        $this->assertCount(2, Post::whereUserCan($user2, $permission1)->get());
        $this->assertCount(2, Post::whereUserCan($user2, $permission2)->get());
    }
}
