<?php

namespace App\Services;

use App\Contracts\DeliveryFeeCalculatorInterface;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\Restaurant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(private DeliveryFeeCalculatorInterface $deliveryFeeCalculator) {}

    /**
     * @param  array{customer_id: int, restaurant_id: int, items: list<array{product_id: int, quantity: int}>}  $data
     */
    public function create(array $data): Order
    {
        $restaurant = Restaurant::findOrFail($data['restaurant_id']);
        $deliveryFee = $this->deliveryFeeCalculator->calculate($restaurant, $data['items']);

        $productIds = collect($data['items'])->pluck('product_id')->unique()->values();

        $products = Product::query()
            ->where('restaurant_id', $restaurant->id)
            ->available()
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        if ($products->count() !== $productIds->count()) {
            throw ValidationException::withMessages([
                'items' => ['One or more products are invalid, unavailable, or do not belong to this restaurant.'],
            ]);
        }

        return DB::transaction(function () use ($data, $products, $deliveryFee) {
            $totalAmount = $deliveryFee;
            $lineItems = [];

            foreach ($data['items'] as $item) {
                $product = $products[$item['product_id']];
                $quantity = (int) $item['quantity'];
                $unitPrice = (float) $product->price;
                $totalAmount += $unitPrice * $quantity;

                $lineItems[] = [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                ];
            }

            $order = Order::create([
                'customer_id' => $data['customer_id'],
                'restaurant_id' => $data['restaurant_id'],
                'status' => OrderStatus::Pending,
                'total_amount' => $totalAmount,
                'delivery_fee' => $deliveryFee,
            ]);

            $order->orderItems()->createMany($lineItems);

            return $this->loadOrderRelations($order);
        });
    }

    public function cancel(Order $order): Order
    {
        if ($order->status !== OrderStatus::Pending) {
            throw ValidationException::withMessages([
                'order' => ['Only pending orders can be cancelled.'],
            ]);
        }

        $order->update(['status' => OrderStatus::Cancelled]);

        return $this->loadOrderRelations($order);
    }

    public function acceptByAdmin(Order $order): Order
    {
        if ($order->status !== OrderStatus::Pending) {
            throw ValidationException::withMessages([
                'order' => ['Only pending orders can be accepted.'],
            ]);
        }

        $order->update(['status' => OrderStatus::Accepted]);

        return $this->loadOrderRelations($order);
    }

    public function acceptByDriver(Order $order, int $driverId): Order
    {
        if ($order->status !== OrderStatus::Accepted) {
            throw ValidationException::withMessages([
                'order' => ['Only accepted orders can be taken by a driver.'],
            ]);
        }

        if ($order->driver_id !== null) {
            throw ValidationException::withMessages([
                'order' => ['This order already has a driver assigned.'],
            ]);
        }

        $alreadyBusy = Order::query()
            ->active()
            ->where('driver_id', $driverId)
            ->exists();

        if ($alreadyBusy) {
            throw ValidationException::withMessages([
                'driver_id' => ['This driver already has an active order.'],
            ]);
        }

        $order->update(['driver_id' => $driverId]);

        return $this->loadOrderRelations($order);
    }

    public function markPickedUp(Order $order): Order
    {
        if ($order->status !== OrderStatus::Accepted || $order->driver_id === null) {
            throw ValidationException::withMessages([
                'order' => ['Only accepted orders with an assigned driver can be marked as picked up.'],
            ]);
        }

        $order->update(['status' => OrderStatus::PickedUp]);

        return $this->loadOrderRelations($order);
    }

    public function markDelivered(Order $order): Order
    {
        if ($order->status !== OrderStatus::PickedUp) {
            throw ValidationException::withMessages([
                'order' => ['Only picked up orders can be marked as delivered.'],
            ]);
        }

        $order->update(['status' => OrderStatus::Delivered]);

        return $this->loadOrderRelations($order);
    }

    private function loadOrderRelations(Order $order): Order
    {
        return $order->load([
            'orderItems:id,order_id,product_id,quantity,unit_price',
            'orderItems.product:id,name,price',
            'restaurant:id,name',
            'driver:id,name',
        ]);
    }
}
