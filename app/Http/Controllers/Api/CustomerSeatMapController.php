<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\CustomerSeatMapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerSeatMapController extends Controller
{
    public function show(Request $request, Event $event, CustomerSeatMapService $seatMap): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $seatMap->getMap($event, $request->user()),
        ]);
    }
}
