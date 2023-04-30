<?php

namespace Back2Lobby\AccessControl\Tests;

use Back2Lobby\AccessControl\Exceptions\InvalidAttributesException;
use Back2Lobby\AccessControl\Exceptions\InvalidRoleableException;
use Back2Lobby\AccessControl\Facades\AccessControlFacade as AccessControl;
use Back2Lobby\AccessControl\Models\Role;
use Back2Lobby\AccessControl\Models\User;
use Back2Lobby\AccessControl\Tests\Models\Company;
use Back2Lobby\AccessControl\Tests\Models\Post;

/**
 * @coversDefaultClass \Back2Lobby\AccessControl\Service\UserRoleCheck
 */
class UserRoleCheckTest extends BaseTestCase
{
    /**
     * @covers ::a
     *
     * @test
     */
    public function it_returns_true_if_user_have_given_role()
    {
        $user = User::factory()->create();

        $role = Role::factory()->createFake();

        $user->assign($role);

        $this->assertTrue(AccessControl::is($user)->a($role));
    }

    /**
     * @covers ::a
     *
     * @test
     */
    public function it_returns_false_if_user_does_not_have_given_role()
    {
        $user = User::factory()->create();

        $role = Role::factory()->createFake();

        $this->assertFalse(AccessControl::is($user)->a($role));
    }

    /**
     * @covers ::a
     *
     * @test
     */
    public function it_returns_true_if_given_role_is_one_of_the_many_roles_that_the_user_have()
    {
        $user = User::factory()->create();

        $role1 = Role::factory()->createFake();
        $role2 = Role::factory()->createFake();
        $role3 = Role::factory()->createFake();

        $user->assign($role1);

        $user->assign($role2);

        $this->assertTrue(AccessControl::is($user)->a($role1));
        $this->assertTrue(AccessControl::is($user)->a($role2));
        $this->assertFalse(AccessControl::is($user)->a($role3));
    }

    /**
     * @covers ::a
     *
     * @test
     */
    public function it_throws_exceptions_if_the_roleable_is_invalid()
    {
        $user = User::factory()->create();

        $role1 = Role::factory()->createFake();
        $role2 = Role::factory()->createFake([
            'roleables' => [Post::class],
        ]);

        $post = Post::factory()->create();

        $user->assign($role1);

        $user->assign($role2, $post);

        $this->assertTrue(AccessControl::is($user)->a($role1));
        $this->assertTrue(AccessControl::is($user)->a($role2, $post));

        $this->assertException(InvalidRoleableException::class, function () use ($user, $role1, $post) {
            $this->assertTrue(AccessControl::is($user)->a($role1, $post));
        });

        $this->assertException(InvalidRoleableException::class, function () use ($user, $role2) {
            $this->assertTrue(AccessControl::is($user)->a($role2, Company::factory()->create()));
        });

        $this->assertException(InvalidRoleableException::class, function () use ($user, $role2) {
            $this->assertTrue(AccessControl::is($user)->a($role2));
        });
    }

    /**
     * @covers ::a
     *
     * @test
     */
    public function it_returns_false_if_roleable_is_valid_but_id_does_not_match()
    {
        $user = User::factory()->create();

        $role = Role::factory()->createFake([
            'roleables' => [Post::class],
        ]);

        $post = Post::factory()->create();

        $user->assign($role, $post);

        $this->assertFalse(AccessControl::is($user)->a($role, Post::factory()->create()));
    }

    /**
     * @covers ::an
     *
     * @test
     */
    public function it_acts_as_alias_function_for_function_a()
    {
        $user = User::factory()->create();

        $role1 = Role::factory()->createFake();
        $role2 = Role::factory()->createFake();

        $user->assign($role1);

        $this->assertTrue(AccessControl::is($user)->a($role1));
        $this->assertTrue(AccessControl::is($user)->an($role1));

        $this->assertFalse(AccessControl::is($user)->a($role2));
        $this->assertFalse(AccessControl::is($user)->an($role2));
    }

    /**
     * @covers ::notA
     *
     * @test
     */
    public function it_checks_if_the_user_does_not_have_a_role_using_function_a()
    {
        $user = User::factory()->create();

        $role1 = Role::factory()->createFake();
        $role2 = Role::factory()->createFake();

        $user->assign($role1);

        $this->assertTrue(AccessControl::is($user)->a($role1));
        $this->assertFalse(AccessControl::is($user)->notA($role1));

        $this->assertFalse(AccessControl::is($user)->a($role2));
        $this->assertTrue(AccessControl::is($user)->notA($role2));
    }

    /**
     * @covers ::notAn
     *
     * @test
     */
    public function it_acts_as_alias_function_for_function_notA()
    {
        $user = User::factory()->create();

        $role1 = Role::factory()->createFake();
        $role2 = Role::factory()->createFake();

        $user->assign($role1);

        $this->assertFalse(AccessControl::is($user)->notA($role1));
        $this->assertFalse(AccessControl::is($user)->notAn($role1));

        $this->assertTrue(AccessControl::is($user)->notA($role2));
        $this->assertTrue(AccessControl::is($user)->notAn($role2));
    }

    /**
     * @covers ::all
     *
     * @test
     */
    public function it_returns_true_only_if_user_have_all_of_the_given_roles()
    {
        $user = User::factory()->create();

        $role1 = Role::factory()->createFake();
        $role2 = Role::factory()->createFake();
        $role3 = Role::factory()->createFake();

        $user->assign($role1);
        $user->assign($role3);

        $this->assertTrue(AccessControl::is($user)->all([
            $role1->name,
            $role3->name,
        ]));

        $this->assertFalse(AccessControl::is($user)->all([
            $role1->name,
            $role2->name,
        ]));

        $this->assertFalse(AccessControl::is($user)->all([
            $role2->name,
        ]));

        $this->assertFalse(AccessControl::is($user)->all([]));

        $this->assertFalse(AccessControl::is($user)->all([
            $role1->name,
            $role2->name,
            $role3->name,
        ]));

        $this->assertFalse(AccessControl::is($user)->all([
            $role1->name,
            $role3->name,
            $role3->name,
        ]));
    }

    /**
     * @covers ::all
     *
     * @test
     */
    public function it_throws_exception_if_string_role_name_is_not_passed_in_given_array()
    {
        $user = User::factory()->create();

        $role1 = Role::factory()->createFake();

        $user->assign($role1);

        $this->expectException(InvalidAttributesException::class);
        $this->assertFalse(AccessControl::is($user)->all([
            $role1->id,
        ]));
    }

    /**
     * @covers ::all
     *
     * @test
     */
    public function it_does_not_check_for_roleables_for_all_roles()
    {
        $user = User::factory()->create();

        $role1 = Role::factory()->createFake(['roleables' => [Post::class]]);
        $role2 = Role::factory()->createFake(['roleables' => [Company::class]]);

        $user->assign($role1, Post::factory()->create());

        $this->assertTrue(AccessControl::is($user)->all([
            $role1->name,
        ]));

        $this->assertFalse(AccessControl::is($user)->all([
            $role1->name,
            $role2->name,
        ]));

        $this->assertFalse(AccessControl::is($user)->all([
            $role2->name,
        ]));
    }

    /**
     * @covers ::any
     *
     * @test
     */
    public function it_returns_true_if_user_have_any_of_the_given_roles()
    {
        $user = User::factory()->create();

        $role1 = Role::factory()->createFake();
        $role2 = Role::factory()->createFake();
        $role3 = Role::factory()->createFake();

        $user->assign($role1);
        $user->assign($role3);

        $this->assertTrue(AccessControl::is($user)->any([
            $role1->name,
            $role2->name,
        ]));

        $this->assertTrue(AccessControl::is($user)->any([
            $role1->name,
            $role2->name,
            $role3->name,
        ]));

        $this->assertFalse(AccessControl::is($user)->any([
            $role2->name,
        ]));

        $this->assertFalse(AccessControl::is($user)->any([]));
    }

    /**
     * @covers ::any
     *
     * @test
     */
    public function it_throws_exception_if_any_of_the_role_given_is_not_string()
    {
        $user = User::factory()->create();

        $role1 = Role::factory()->createFake();

        $user->assign($role1);

        $this->expectException(InvalidAttributesException::class);
        $this->assertFalse(AccessControl::is($user)->any([
            $role1->id,
        ]));
    }

    /**
     * @covers ::any
     *
     * @test
     */
    public function it_does_not_check_for_roleables_any_role()
    {
        $user = User::factory()->create();

        $role1 = Role::factory()->createFake(['roleables' => [Post::class]]);
        $role2 = Role::factory()->createFake(['roleables' => [Company::class]]);

        $user->assign($role1, Post::factory()->create());

        $this->assertTrue(AccessControl::is($user)->any([
            $role1->name,
        ]));

        $this->assertTrue(AccessControl::is($user)->any([
            $role1->name,
            $role2->name,
        ]));

        $this->assertFalse(AccessControl::is($user)->any([
            $role2->name,
        ]));
    }
}
