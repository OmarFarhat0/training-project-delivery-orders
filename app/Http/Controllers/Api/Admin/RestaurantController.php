<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRestaurantRequest;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;

class RestaurantController extends Controller
{
    public function index(): JsonResponse
    {
        $restaurants = Restaurant::withoutGlobalScope('active')
            ->latest()
            ->get();

        return $this->success($restaurants);
    }

    public function store(StoreRestaurantRequest $request): JsonResponse
    {
        $restaurant = Restaurant::withoutGlobalScope('active')->create([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->success($restaurant, 201);
    }

    public function suspend(int $restaurant): JsonResponse
    {
        $restaurant = $this->findRestaurant($restaurant);
        $restaurant->update(['is_active' => false]);

        return $this->success($restaurant);
    }

    public function activate(int $restaurant): JsonResponse
    {
        $restaurant = $this->findRestaurant($restaurant);
        $restaurant->update(['is_active' => true]);

        return $this->success($restaurant);
    }

    private function findRestaurant(int $restaurantId): Restaurant
    {
        return Restaurant::withoutGlobalScope('active')->findOrFail($restaurantId);
    }
}
