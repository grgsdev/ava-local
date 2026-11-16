<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class InteractionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type' => 'single_choice',
            'timestamp' => $this->faker->randomFloat(2, 5, 50),
            'data' => [
                'question' => $this->faker->sentence() . '?',
                'answers' => [
                    ['text' => 'Resposta A', 'is_correct' => true, 'action' => 'continue', 'target' => null],
                    ['text' => 'Resposta B', 'is_correct' => false, 'action' => 'continue', 'target' => null],
                ]
            ],
        ];
    }
}
