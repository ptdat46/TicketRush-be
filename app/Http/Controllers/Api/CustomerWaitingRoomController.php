<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\WaitingRoomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerWaitingRoomController extends Controller
{
    public function store(Request $request, Event $event, WaitingRoomService $waitingRoomService): JsonResponse
    {
        $payload = $waitingRoomService->join($event, $request->user());

        return response()->json([
            'success' => true,
            'message' => $payload['can_enter_booking']
                ? 'You can enter the booking page now.'
                : 'You are in the waiting room.',
            'data' => $payload,
        ]);
    }

    public function show(Request $request, Event $event, WaitingRoomService $waitingRoomService): JsonResponse
    {
        $payload = $waitingRoomService->status($event, $request->user());

        return response()->json([
            'success' => true,
            'data' => $payload,
        ]);
    }

    public function destroy(Request $request, Event $event, WaitingRoomService $waitingRoomService): JsonResponse
    {
        $payload = $waitingRoomService->leave($event, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'You have left the waiting room.',
            'data' => $payload,
        ]);
    }
}
