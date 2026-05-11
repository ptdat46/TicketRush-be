<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminUpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\JsonResponse;

class AdminEventController extends Controller
{
    public function update(AdminUpdateEventRequest $request, Event $event): JsonResponse
    {
        $event->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Event updated successfully.',
            'data' => new EventResource($event->refresh()),
        ]);
    }
}
