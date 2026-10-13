<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Admin',
            'email' => 'admin@tawseela.test',
            'phone' => '0900000001',
        ]);

        $customers = User::factory(50)->customer()->create();
        $drivers = User::factory(20)->driver()->create();

        $restaurants = Restaurant::factory(20)->create();
        Restaurant::factory(5)->suspended()->create();

        foreach (Restaurant::withoutGlobalScope('active')->get() as $restaurant) {
            Product::factory(15)->create([
                'restaurant_id' => $restaurant->id,
            ]);

            Product::factory(2)->unavailable()->create([
                'restaurant_id' => $restaurant->id,
            ]);
        }

        $restaurants = Restaurant::withoutGlobalScope('active')
            ->with('products')
            ->get();

        for ($i = 0; $i < 3000; $i++) {
            $restaurant = $restaurants->random();
            $products = $restaurant->products->where('is_available', true)->values();

            if ($products->isEmpty()) {
                continue;
            }

            $status = fake()->randomElement(OrderStatus::cases());
            $driverId = null;

            if (in_array($status, [OrderStatus::PickedUp, OrderStatus::Delivered], true)) {
                $driverId = $drivers->random()->id;
            } elseif ($status === OrderStatus::Accepted && fake()->boolean(50)) {
                $driverId = $drivers->random()->id;
            }

            $count = min(fake()->numberBetween(1, 3), $products->count());
            $selected = $products->random($count);
            $selected = $selected instanceof Product ? collect([$selected]) : $selected;

            $deliveryFee = 10;
            $totalAmount = $deliveryFee;
            $items = [];

            foreach ($selected as $product) {
                $quantity = fake()->numberBetween(1, 3);
                $totalAmount += $product->price * $quantity;

                $items[] = [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                ];
            }

            $createdAt = fake()->dateTimeBetween('-60 days', 'now');

            $order = Order::create([
                'customer_id' => $customers->random()->id,
                'restaurant_id' => $restaurant->id,
                'driver_id' => $driverId,
                'status' => $status,
                'total_amount' => $totalAmount,
                'delivery_fee' => $deliveryFee,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            $order->orderItems()->createMany($items);
        }
    }
}
