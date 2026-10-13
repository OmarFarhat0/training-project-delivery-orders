<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\AcceptDriverOrderRequest;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DriverOrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

    public function available(): JsonResponse
    {
        $orders = Order::query()
            ->availableForDriver()
            ->with([
                'orderItems:id,order_id,product_id,quantity,unit_price',
                'orderItems.product:id,name,price',
                'restaurant:id,name',
                'driver:id,name',
            ])
            ->latest()
            ->get();

        return $this->success($orders);
    }

    public function index(User $driver): JsonResponse
    {
        if ($driver->role !== UserRole::Driver) {
            throw new NotFoundHttpException('Driver not found.');
        }

        $orders = $driver->ordersAsDriver()
            ->with([
                'orderItems:id,order_id,product_id,quantity,unit_price',
                'orderItems.product:id,name,price',
                'restaurant:id,name',
                'driver:id,name',
            ])
            ->latest()
            ->get();


        return $this->success($orders);
    }

    public function accept(AcceptDriverOrderRequest $request, Order $order): JsonResponse
    {
        $order = $this->orderService->acceptByDriver(
            $order,
            (int) $request->validated('driver_id'),
        );

        return $this->success($order);
    }

    public function pickedUp(Order $order): JsonResponse
    {
        $order = $this->orderService->markPickedUp($order);

        return $this->success($order);
    }

    public function delivered(Order $order): JsonResponse
    {
        $order = $this->orderService->markDelivered($order);

        return $this->success($order);
    }
}
