<?php

namespace Back2Lobby\AccessControl\Tests\Factories;

use Back2Lobby\AccessControl\Tests\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'title' => fake()->unique()->sentence(4),
            'body' => fake()->unique()->paragraph(3),
        ];
    }
}
