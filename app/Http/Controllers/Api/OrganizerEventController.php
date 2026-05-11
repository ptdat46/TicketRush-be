<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;

class OrganizerEventController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $events = Event::query()
            ->where('organizer_id', $request->user()->id)
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->string('category')))
            ->when($request->filled('starts_after'), fn ($query) => $query->where('starts_at', '>=', $request->date('starts_after')))
            ->when($request->filled('starts_before'), fn ($query) => $query->where('starts_at', '<=', $request->date('starts_before')))
            ->latest()
            ->paginate((int) $request->integer('per_page', 12));

        return EventResource::collection($events);
    }

    public function store(StoreEventRequest $request): JsonResponse
    {
        $event = Event::create([
            ...$request->validated(),
            'organizer_id' => $request->user()->id,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Event created successfully and is waiting for admin approval.',
            'data' => new EventResource($event),
        ], 201);
    }

    public function show(Request $request, Event $event): JsonResponse
    {
        if ((int) $event->organizer_id !== (int) $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view this event.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new EventResource($event->load('organizer')),
        ]);
    }

    public function update(UpdateEventRequest $request, Event $event): JsonResponse
    {
        if ((int) $event->organizer_id !== (int) $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update this event.',
            ], 403);
        }

        $event->update([
            ...$request->validated(),
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Event updated successfully and is waiting for admin approval again.',
            'data' => new EventResource($event->refresh()),
        ]);
    }

    public function destroy(Request $request, Event $event): JsonResponse
    {
        if ((int) $event->organizer_id !== (int) $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete this event.',
            ], 403);
        }

        $event->delete();

        return response()->json([
            'success' => true,
            'message' => 'Event deleted successfully.',
        ]);
    }
}
