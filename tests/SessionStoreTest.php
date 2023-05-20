<?php

namespace Back2Lobby\AccessControl\Tests;

use Back2Lobby\AccessControl\Facades\AccessControlFacade;
use Back2Lobby\AccessControl\Models\Role;
use Back2Lobby\AccessControl\Tests\Models\User;

/**
 * @coversDefaultClass \Back2Lobby\AccessControl\Stores\SessionStore
 */
class SessionStoreTest extends BaseTestCase
{
    /**
     * @covers ::setUser
     *
     * @test
     */
    public function it_sets_only_the_currently_authenticated_user()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->assertNull(AccessControlFacade::getSessionStore()->getAuthUser());

        $this->actingAs($user1);

        $this->assertFalse(AccessControlFacade::getSessionStore()->getAuthUser()->id === $user2->id);

        $this->assertNotNull(AccessControlFacade::getSessionStore()->getAuthUser());
    }

    /**
     * @covers ::getUser
     *
     * @test
     */
    public function it_resets_role_user_maps_on_setting_user()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $role = Role::factory()->createFake();
        $user2->assign($role);

        $this->actingAs($user1);

        $this->assertCount(0, AccessControlFacade::getSessionStore()->getAssignedRoles());

        $this->actingAs($user2);

        $this->assertCount(1, AccessControlFacade::getSessionStore()->getAssignedRoles());
    }

    /**
     * @covers ::getRoleUserMaps
     *
     * @test
     */
    public function it_gives_assigned_roles_only_when_user_is_available()
    {
        $user = User::factory()->create();
        $role = Role::factory()->createFake();

        $user->assign($role);

        $this->assertNull(AccessControlFacade::getSessionStore()->getAssignedRoles());

        $this->actingAs($user);

        $this->assertNotNull(AccessControlFacade::getSessionStore()->getAssignedRoles());
        $this->assertCount(1, AccessControlFacade::getSessionStore()->getAssignedRoles());
    }
}
