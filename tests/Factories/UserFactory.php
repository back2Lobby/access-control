<?php

namespace Back2Lobby\AccessControl\Tests\Factories;

use Back2Lobby\AccessControl\Tests\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => fake()->unique()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => null,
            'password' => '$2y$10$lMZEOXA55tFhfPfW3XmBEuKV0YNXyn19iigowppBPvmhm2CP1aEZC', // password
            'remember_token' => Str::random(10),
        ];
    }

    public function create($attributes = [], ?Model $parent = null): User
    {
        return parent::create(...func_get_args());
    }
}
