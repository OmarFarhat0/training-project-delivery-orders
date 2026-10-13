<?php

namespace Database\Factories;

use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Restaurant>
 */
class RestaurantFactory extends Factory
{
    protected $model = Restaurant::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'address' => fake()->address(),
            'phone' => fake()->numerify('09########'),
            'is_active' => true,
        ];
    }

    public function suspended(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }
}
