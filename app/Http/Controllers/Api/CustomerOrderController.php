<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CustomerOrderController extends Controller
{
    public function index(User $customer): JsonResponse
    {
        if ($customer->role !== UserRole::Customer) {
            throw new NotFoundHttpException('Customer not found.');
        }

        $orders = $customer->ordersAsCustomer()
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
}
