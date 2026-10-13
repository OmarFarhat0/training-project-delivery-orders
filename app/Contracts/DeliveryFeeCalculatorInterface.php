<?php

namespace App\Contracts;

use App\Models\Restaurant;

interface DeliveryFeeCalculatorInterface
{
    public function calculate(Restaurant $restaurant, array $items): float;
}
