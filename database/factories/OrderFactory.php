<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'customer_id' => User::factory()->customer(),
            'restaurant_id' => Restaurant::factory(),
            'driver_id' => null,
            'status' => OrderStatus::Pending,
            'total_amount' => fake()->randomFloat(2, 20, 500),
            'delivery_fee' => 10,
        ];
    }
}
