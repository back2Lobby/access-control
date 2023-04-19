<?php

namespace Back2Lobby\AccessControl\Tests\Factories;

use Back2Lobby\AccessControl\Tests\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
        ];
    }
}
