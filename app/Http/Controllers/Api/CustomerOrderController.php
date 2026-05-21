<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutSeatsRequest;
use App\Http\Requests\CustomerOrderIndexRequest;
use App\Http\Resources\OrderResource;
use App\Models\Event;
use App\Models\Order;
use App\Repositories\CustomerOrderRepository;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerOrderController extends Controller
{
    public function __construct(
        private readonly CustomerOrderRepository $orders,
    ) {}

    public function store(CheckoutSeatsRequest $request, Event $event, CheckoutService $checkoutService): JsonResponse
    {
        $data = $request->validated();

        $order = $checkoutService->checkout(
            $event,
            $request->user(),
            $data['seat_ids'],
            $data['payment_method'] ?? 'mock',
            $data['payment_reference'] ?? null,
        );

        return response()->json([
            'success' => true,
            'message' => 'Checkout completed successfully.',
            'data' => new OrderResource($order),
        ], 201);
    }

    public function index(CustomerOrderIndexRequest $request): JsonResponse
    {
        $orders = $this->orders->paginateForCustomer($request->user(), $request->filters());

        return response()->json([
            'success' => true,
            'data' => OrderResource::collection($orders),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        if ((int) $order->customer_id !== (int) $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view this order.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new OrderResource($order->load(['event', 'tickets.seat.zone'])),
        ]);
    }
}
