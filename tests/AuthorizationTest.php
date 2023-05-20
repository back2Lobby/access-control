<?php

namespace Back2Lobby\AccessControl\Tests;

use Back2Lobby\AccessControl\Models\Permission;
use Back2Lobby\AccessControl\Models\Role;
use Back2Lobby\AccessControl\Tests\Models\Post;
use Back2Lobby\AccessControl\Tests\Models\User;
use Illuminate\Support\Facades\Route;

class AuthorizationTest extends BaseTestCase
{
    /**
     * @coversNothing
     *
     * @test
     */
    public function it_authorizes_routes_properly()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $role1 = Role::factory()->createFake();
        $role2 = Role::factory()->createFake(['roleables' => [Post::class]]);

        $permission1 = Permission::factory()->createFake(['name' => 'view-dashboard']);
        $permission2 = Permission::factory()->createFake(['name' => 'edit-post']);

        $post = Post::factory()->create();

        $role1->allow($permission1);
        $role2->allow($permission2);

        $user1->assign($role1);
        $user2->assign($role2, $post);

        Route::get('/dashboard', function () {
            return 'dashboard';
        })->middleware('can:view-dashboard');

        $response = $this->actingAs($user1)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSeeText('dashboard');

        Route::get('/users/{user}/posts/{post}/edit', function () {
            return 'edit-post-form';
        })->middleware('can:edit-post,'.Post::class);
        $response = $this->actingAs($user2)->get('/users/1/posts/1/edit');
        $response->assertStatus(200);
        $response->assertSeeText('edit-post-form');

        $response = $this->actingAs($user1)->get('/users/1/posts/1/edit');
        $response->assertStatus(403);
    }

    /**
     * @coversNothing
     *
     * @test
     */
    public function it_authorizes_can_method_on_user_model_properly()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $role1 = Role::factory()->createFake();
        $role2 = Role::factory()->createFake(['roleables' => [Post::class]]);

        $permission1 = Permission::factory()->createFake(['name' => 'view-dashboard']);
        $permission2 = Permission::factory()->createFake(['name' => 'edit-post']);

        $post = Post::factory()->create();

        $role1->allow($permission1);
        $role2->allow($permission2);

        $user1->assign($role1);
        $user2->assign($role2, $post);

        $this->assertTrue($user1->can('view-dashboard'));
        $this->assertFalse($user1->can('edit-post'));
        $this->assertFalse($user1->can('edit-post', $post));

        $this->assertFalse($user2->can('view-dashboard'));
        $this->assertFalse($user2->can('edit-post'));
        $this->assertTrue($user2->can('edit-post', $post));
    }
}
