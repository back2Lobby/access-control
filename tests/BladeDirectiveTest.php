<?php

namespace Back2Lobby\AccessControl\Tests;

use Back2Lobby\AccessControl\Models\Permission;
use Back2Lobby\AccessControl\Models\Role;
use Back2Lobby\AccessControl\Tests\Models\Post;
use Back2Lobby\AccessControl\Tests\Models\User;
use Illuminate\Support\Facades\View;

class BladeDirectiveTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        View::addLocation(__DIR__.'/resources/views');
    }

    /**
     * @coversNothing
     *
     * @test
     */
    public function it_renders_blade_data_based_on_can_directive(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $role1 = Role::factory()->createFake();
        $role2 = Role::factory()->createFake(['roleables' => [Post::class]]);

        $permission1 = Permission::factory()->createFake(['name' => 'create-post']);
        $permission2 = Permission::factory()->createFake(['name' => 'edit-post']);

        $post = Post::factory()->create();

        $role1->allow($permission1);
        $role2->allow($permission2);

        $user1->assign($role1);
        $user2->assign($role2, $post);

        // testing first user can view create post
        $this->actingAs($user1);

        $html = view('can')->render();
        $this->assertStringContainsString('Create Post', $html);

        // making sure second user cannot view create post
        $this->actingAs($user2);

        $html = view('can')->render();
        $this->assertStringNotContainsString('Create Post', $html);

        // testing first user cannot view edit post with roleable
        $this->actingAs($user1);

        $html = view('can')->render();
        $this->assertStringNotContainsString('Edit Post', $html);

        // testing second user can view edit post with roleable
        $this->actingAs($user2);

        $html = view('can')->render();
        $this->assertStringContainsString('Edit Post', $html);
    }
}
