<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => $this->faker->bs(),
            'type' => 'single_video',
            'status' => 'Em Edição',
        ];
    }
}
