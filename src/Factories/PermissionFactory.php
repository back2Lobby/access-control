<?php

namespace Back2Lobby\AccessControl\Factories;

use Back2Lobby\AccessControl\Facades\AccessControlFacade as AccessControl;
use Back2Lobby\AccessControl\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PermissionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Permission::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $permissionTitle = fake()->unique()->sentence(2);

        return [
            'name' => Str::slug($permissionTitle),
            'title' => $permissionTitle,
        ];
    }

    /**
     * Create a permission with AccessControl using fake data
     *
     * */
    public function createFake(array $attributes = []): Permission
    {
        return AccessControl::createPermission($this->make($attributes)->attributesToArray());
    }
}
