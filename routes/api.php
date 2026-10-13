<?php

use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\RestaurantController as AdminRestaurantController;
use App\Http\Controllers\Api\Admin\RestaurantProductController as AdminRestaurantProductController;
use App\Http\Controllers\Api\CustomerOrderController;
use App\Http\Controllers\Api\DriverOrderController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\RestaurantController;
use App\Http\Controllers\Api\RestaurantProductController;
use Illuminate\Support\Facades\Route;

Route::get('/restaurants', [RestaurantController::class, 'index']);
Route::get('/restaurants/{restaurant}/products', [RestaurantProductController::class, 'index']);

Route::post('/orders', [OrderController::class, 'store']);
Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);

Route::get('/customers/{customer}/orders', [CustomerOrderController::class, 'index']);

Route::get('/driver/orders/available', [DriverOrderController::class, 'available']);
Route::post('/driver/orders/{order}/accept', [DriverOrderController::class, 'accept']);
Route::post('/driver/orders/{order}/picked-up', [DriverOrderController::class, 'pickedUp']);
Route::post('/driver/orders/{order}/delivered', [DriverOrderController::class, 'delivered']);
Route::get('/drivers/{driver}/orders', [DriverOrderController::class, 'index']);

Route::get('/admin/restaurants', [AdminRestaurantController::class, 'index']);
Route::post('/admin/restaurants', [AdminRestaurantController::class, 'store']);
Route::post('/admin/restaurants/{restaurant}/suspend', [AdminRestaurantController::class, 'suspend']);
Route::post('/admin/restaurants/{restaurant}/activate', [AdminRestaurantController::class, 'activate']);
Route::get('/admin/restaurants/{restaurant}/products', [AdminRestaurantProductController::class, 'index']);
Route::post('/admin/restaurants/{restaurant}/products', [AdminRestaurantProductController::class, 'store']);
Route::get('/admin/orders', [AdminOrderController::class, 'index']);
Route::post('/admin/orders/{order}/accept', [AdminOrderController::class, 'accept']);
