<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PromoCode>
 */
class PromoCodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->bothify('PROMO###??')), // e.g., PROMO123AB
            'value' => $this->faker->randomFloat(2, 5, 50), // random discount value
            'uses_limit' => $this->faker->numberBetween(1, 100),
            'start_time' => now(),
            'end_time' => now()->addDays($this->faker->numberBetween(5, 30)),
            'status' => $this->faker->randomElement(['active', 'inactive']),
        ];
    }
}
