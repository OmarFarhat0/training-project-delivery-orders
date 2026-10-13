<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'name' => fake()->words(3, true),
            'price' => fake()->randomFloat(2, 5, 100),
            'is_available' => true,
        ];
    }

    public function unavailable(): static
    {
        return $this->state(fn () => [
            'is_available' => false,
        ]);
    }
}
