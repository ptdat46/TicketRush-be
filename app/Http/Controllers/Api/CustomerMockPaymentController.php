<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutSeatsRequest;
use App\Http\Resources\OrderResource;
use App\Models\Event;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;

class CustomerMockPaymentController extends Controller
{
    public function success(CheckoutSeatsRequest $request, Event $event, CheckoutService $checkoutService): JsonResponse
    {
        $data = $request->validated();

        $order = $checkoutService->checkout(
            $event,
            $request->user(),
            $data['seat_ids'],
            'mock',
            $data['payment_reference'] ?? null,
        );

        return response()->json([
            'success' => true,
            'message' => 'Mock payment confirmed successfully.',
            'data' => new OrderResource($order),
        ], 201);
    }
}
