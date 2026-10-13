<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminOrderIndexRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

    public function index(AdminOrderIndexRequest $request): JsonResponse
    {
        $filter = $request->validated('filter');

        $orders = Order::query()
            ->when($filter === 'active', fn ($query) => $query->active())
            ->when($filter === 'today', fn ($query) => $query->today())
            ->when($filter === 'late', fn ($query) => $query->late())
            ->with([
                'customer:id,name',
                'restaurant' => fn ($query) => $query
                    ->withoutGlobalScope('active')
                    ->select('id', 'name'),
                'driver:id,name',
            ])
            ->withCount('orderItems')
            ->latest()
            ->get();

        return $this->success($orders);
    }

    public function accept(Order $order): JsonResponse
    {
        $order = $this->orderService->acceptByAdmin($order);

        return $this->success($order);
    }
}
