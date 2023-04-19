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
 * @coversDefaultClass \Back2Lobby\AccessControl\Service\RetractRole
 */
class RetractRoleTest extends BaseTestCase
{
    /**
     * @covers ::to
     *
     * @test
     */
    public function it_retracts_role_without_roleable_from_the_given_user()
    {
        $user = User::factory()->create();

        $role = Role::factory()->createFake();

        AccessControl::assign($role)->to($user);

        $this->assertCount(1, User::whereIs($role)->get());

        $this->assertTrue(AccessControl::retract($role)->from($user));

        $this->assertCount(0, User::whereIs($role)->get());
        $this->assertCount(0, DB::table('role_user')->get());
    }

    /**
     * @covers ::to
     *
     * @test
     */
    public function it_retracts_role_with_roleable_from_the_given_user()
    {
        $user = User::factory()->create();

        $role = Role::factory()->createFake([
            'roleables' => [Post::class],
        ]);

        $post = Post::factory()->create();

        AccessControl::assign($role, $post)->to($user);

        $this->assertCount(1, User::whereIs($role, $post)->get());

        $this->assertTrue(AccessControl::retract($role, $post)->from($user));

        $this->assertCount(0, User::whereIs($role, $post)->get());
        $this->assertCount(0, DB::table('role_user')->get());
    }

    /**
     * @covers ::to
     *
     * @test
     */
    public function it_returns_true_only_if_role_was_retracted_correctly_in_database()
    {
        $user = User::factory()->create();

        $role = Role::factory()->createFake();

        AccessControl::assign($role)->to($user);

        $this->assertCount(1, $user->belongsToMany(Role::class)->where('id', $role->id)->get());

        $this->assertTrue(AccessControl::retract($role)->from($user));

        $this->assertCount(0, $user->belongsToMany(Role::class)->where('id', $role->id)->get());

        $this->assertFalse(AccessControl::retract($role)->from($user));
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

        AccessControl::assign($role1)->to($user);
        AccessControl::assign($role2, $post)->to($user);

        $this->assertException(InvalidRoleableException::class, function () use ($role1, $post, $user) {
            AccessControl::retract($role1, $post)->from($user);
        });

        $this->assertException(InvalidRoleableException::class, function () use ($role2, $company, $user) {
            AccessControl::retract($role2, $company)->from($user);
        });

        $this->assertCount(2, $user->belongsToMany(Role::class)->get());
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

        AccessControl::assign($role)->to($user);

        $this->expectException(InvalidUserException::class);
        AccessControl::retract($role)->from(999);

        $this->assertCount(1, $user->belongsToMany(Role::class)->get());
    }
}
