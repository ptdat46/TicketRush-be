<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreZoneRequest;
use App\Http\Requests\UpdateZoneRequest;
use App\Http\Resources\ZoneResource;
use App\Models\Event;
use App\Models\Zone;
use App\Services\EventSeatCounterService;
use App\Services\ZoneSeatGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizerZoneController extends Controller
{
    private function ensureOwnership(Request $request, Event $event): ?JsonResponse
    {
        if ((int) $event->organizer_id !== (int) $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage this event.',
            ], 403);
        }

        return null;
    }

    public function index(Request $request, Event $event): JsonResponse
    {
        if ($error = $this->ensureOwnership($request, $event)) {
            return $error;
        }

        $zones = $event->zones()->withCount('seats')->get();

        return response()->json([
            'success' => true,
            'data' => ZoneResource::collection($zones),
        ]);
    }

    public function store(
        StoreZoneRequest $request,
        Event $event,
        EventSeatCounterService $seatCounter,
        ZoneSeatGeneratorService $seatGenerator,
    ): JsonResponse {
        if ($error = $this->ensureOwnership($request, $event)) {
            return $error;
        }

        $data = $request->validated();
        $data['event_id'] = $event->id;
        $data['is_seating'] = $data['is_seating'] ?? true;

        $zone = Zone::create($data);

        $seatGenerator->generateForZone($zone);
        $seatCounter->sync($event);

        return response()->json([
            'success' => true,
            'message' => 'Zone created successfully.',
            'data' => new ZoneResource($zone->loadCount('seats')),
        ], 201);
    }

    public function show(Request $request, Event $event, Zone $zone): JsonResponse
    {
        if ($error = $this->ensureOwnership($request, $event)) {
            return $error;
        }

        if ((int) $zone->event_id !== (int) $event->id) {
            return response()->json([
                'success' => false,
                'message' => 'Zone does not belong to this event.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new ZoneResource($zone->load(['seats'])->loadCount('seats')),
        ]);
    }

    public function update(UpdateZoneRequest $request, Event $event, Zone $zone, EventSeatCounterService $seatCounter): JsonResponse
    {
        if ($error = $this->ensureOwnership($request, $event)) {
            return $error;
        }

        if ((int) $zone->event_id !== (int) $event->id) {
            return response()->json([
                'success' => false,
                'message' => 'Zone does not belong to this event.',
            ], 404);
        }

        $zone->update($request->validated());
        $seatCounter->sync($event);

        return response()->json([
            'success' => true,
            'message' => 'Zone updated successfully.',
            'data' => new ZoneResource($zone->refresh()->loadCount('seats')),
        ]);
    }

    public function destroy(Request $request, Event $event, Zone $zone, EventSeatCounterService $seatCounter): JsonResponse
    {
        if ($error = $this->ensureOwnership($request, $event)) {
            return $error;
        }

        if ((int) $zone->event_id !== (int) $event->id) {
            return response()->json([
                'success' => false,
                'message' => 'Zone does not belong to this event.',
            ], 404);
        }

        $zone->delete();
        $seatCounter->sync($event);

        return response()->json([
            'success' => true,
            'message' => 'Zone deleted successfully.',
        ]);
    }
}
