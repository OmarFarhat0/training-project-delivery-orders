<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;

class RestaurantProductController extends Controller
{
    public function index(Restaurant $restaurant): JsonResponse
    {
        $products = $restaurant->products()
            ->available()
            ->latest()
            ->get();

        return $this->success($products);
    }
}
