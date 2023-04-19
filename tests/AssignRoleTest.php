<?php

namespace Back2Lobby\AccessControl\Tests;

use Back2Lobby\AccessControl\Exceptions\InvalidRoleableException;
use Back2Lobby\AccessControl\Exceptions\InvalidUserException;
use Back2Lobby\AccessControl\Facades\AccessControlFacade as AccessControl;
use Back2Lobby\AccessControl\Models\Role;
use Back2Lobby\AccessControl\Models\User;
use Back2Lobby\AccessControl\Tests\Models\Company;
use Back2Lobby\AccessControl\Tests\Models\Post;
use Illuminate\Support\Facades\DB;

/**
 * @coversDefaultClass \Back2Lobby\AccessControl\Service\AssignRole
 */
class AssignRoleTest extends BaseTestCase
{
    /**
     * @covers ::to
     *
     * @test
     */
    public function it_assigns_role_without_roleable_to_the_given_user()
    {
        $user = User::factory()->create();

        $role = Role::factory()->createFake();

        $this->assertTrue(AccessControl::assign($role)->to($user));

        $this->assertCount(1, User::whereIs($role)->get());
        $this->assertSame($user->id, User::whereIs($role)->first()->id);
        $this->assertCount(1, DB::table('role_user')->get());
    }

    /**
     * @covers ::to
     *
     * @test
     */
    public function it_assigns_role_with_roleable_to_the_given_user()
    {
        $user = User::factory()->create();

        $role = Role::factory()->createFake([
            'roleables' => [Post::class],
        ]);

        $post = Post::factory()->create();

        $this->assertTrue(AccessControl::assign($role, $post)->to($user));

        $this->assertCount(1, User::whereIs($role, $post)->get());
        $this->assertSame($user->id, User::whereIs($role, $post)->first()->id);
        $this->assertCount(1, DB::table('role_user')->get());
    }

    /**
     * @covers ::to
     *
     * @test
     */
    public function it_returns_true_only_if_role_was_assigned_correctly_in_database()
    {
        $user = User::factory()->create();

        $role = Role::factory()->createFake();

        $this->assertTrue(AccessControl::assign($role)->to($user));
        $this->assertCount(1, $user->belongsToMany(Role::class)->where('id', $role->id)->get());

        $this->assertFalse(AccessControl::assign($role)->to($user));
        $this->assertCount(1, $user->belongsToMany(Role::class)->where('id', $role->id)->get());
    }

    /**
     * @covers ::to
     *
     * @test
     */
    public function it_throws_exception_if_roleable_passed_is_not_valid()
    {
        $user = User::factory()->create();

        $role1 = Role::factory()->createFake();
        $role2 = Role::factory()->createFake([
            'roleables' => [Post::class],
        ]);

        $post = Post::factory()->create();
        $company = Company::factory()->create();

        $this->assertException(InvalidRoleableException::class, function () use ($role1, $post, $user) {
            AccessControl::assign($role1, $post)->to($user);
        });

        $this->assertException(InvalidRoleableException::class, function () use ($role2, $company, $user) {
            AccessControl::assign($role2, $company)->to($user);
        });

        $this->assertCount(0, $user->belongsToMany(Role::class)->get());
    }

    /**
     * @covers ::to
     *
     * @test
     */
    public function it_throws_exception_if_user_or_id_passed_is_not_found_in_database()
    {
        $user = User::factory()->create();
        $role = Role::factory()->createFake();

        $this->expectException(InvalidUserException::class);
        AccessControl::assign($role)->to(999);

        $this->assertCount(0, $user->belongsToMany(Role::class)->get());
    }
}
