<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\Customer\SeatMapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeatMapController extends Controller
{
    public function show(Request $request, Event $event, SeatMapService $seatMap): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $seatMap->getMap($event, $request->user()),
        ]);
    }
}
