<?php

namespace Back2Lobby\AccessControl\Factories;

use Back2Lobby\AccessControl\Facades\AccessControlFacade as AccessControl;
use Back2Lobby\AccessControl\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class RoleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Role::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $roleTitle = fake()->unique()->jobTitle();

        return [
            'name' => Str::slug($roleTitle),
            'title' => $roleTitle,
            'roleables' => null,
        ];
    }

    /**
     * Create a role with AccessControl using fake data
     *
     * */
    public function createFake(array $attributes = []): Role
    {
        return AccessControl::createRole($this->make($attributes)->attributesToArray());
    }
}
