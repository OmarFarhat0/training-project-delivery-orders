<?php

namespace App\Services\DeliveryFee;

use App\Contracts\DeliveryFeeCalculatorInterface;
use App\Models\Product;
use App\Models\Restaurant;

class PercentageDeliveryFeeCalculator implements DeliveryFeeCalculatorInterface
{
    public function calculate(Restaurant $restaurant, array $items): float
    {
        $subtotal = 0;

        foreach ($items as $item) {
            $product = Product::query()
                ->where('restaurant_id', $restaurant->id)
                ->find($item['product_id']);

            if ($product) {
                $subtotal += $product->price * $item['quantity'];
            }
        }

        $fee = $subtotal * 0.10;

        if ($fee < 10) {
            $fee = 10;
        }

        return round($fee, 2);
    }
}
