<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;

class RestaurantProductController extends Controller
{
    public function index(int $restaurant): JsonResponse
    {
        $restaurant = $this->findRestaurant($restaurant);

        $products = $restaurant->products()
            ->select('id', 'restaurant_id', 'name', 'price', 'is_available')
            ->latest()
            ->get();

        return $this->success($products);
    }

    public function store(StoreProductRequest $request, int $restaurant): JsonResponse
    {
        $restaurant = $this->findRestaurant($restaurant);

        $product = $restaurant->products()->create([
            ...$request->validated(),
            'is_available' => $request->boolean('is_available', true),
        ]);

        return $this->success($product, 201);
    }

    private function findRestaurant(int $restaurantId): Restaurant
    {
        return Restaurant::withoutGlobalScope('active')->findOrFail($restaurantId);
    }
}
