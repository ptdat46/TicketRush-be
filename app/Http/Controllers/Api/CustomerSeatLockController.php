<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LockSeatsRequest;
use App\Http\Resources\SeatResource;
use App\Models\Event;
use App\Services\SeatLockService;
use Illuminate\Http\JsonResponse;

class CustomerSeatLockController extends Controller
{
    public function store(LockSeatsRequest $request, Event $event, SeatLockService $seatLockService): JsonResponse
    {
        $seats = $seatLockService->lockSeats($event, $request->user(), $request->validated('seat_ids'));

        return response()->json([
            'success' => true,
            'message' => 'Seats locked successfully.',
            'lock_minutes' => $seatLockService->lockMinutes(),
            'data' => SeatResource::collection($seats),
        ]);
    }

    public function destroy(LockSeatsRequest $request, Event $event, SeatLockService $seatLockService): JsonResponse
    {
        $seats = $seatLockService->releaseSeats($event, $request->user(), $request->validated('seat_ids'));

        return response()->json([
            'success' => true,
            'message' => 'Seats released successfully.',
            'data' => SeatResource::collection($seats),
        ]);
    }
}
