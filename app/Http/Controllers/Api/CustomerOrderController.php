<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiProblemException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutSeatsRequest;
use App\Http\Resources\OrderResource;
use App\Models\Event;
use App\Models\Order;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerOrderController extends Controller
{
    public function store(CheckoutSeatsRequest $request, Event $event, CheckoutService $checkoutService): JsonResponse
    {
        $data = $request->validated();

        try {
            $order = $checkoutService->checkout(
                $event,
                $request->user(),
                $data['seat_ids'],
                $data['payment_method'] ?? 'mock',
                $data['payment_reference'] ?? null,
            );
        } catch (ApiProblemException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'errors' => $exception->errors() ?: null,
            ], $exception->statusCode());
        }

        return response()->json([
            'success' => true,
            'message' => 'Checkout completed successfully.',
            'data' => new OrderResource($order),
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->where('customer_id', $request->user()->id)
            ->with('event')
            ->withCount('tickets')
            ->latest()
            ->paginate((int) $request->integer('per_page', 12));

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
