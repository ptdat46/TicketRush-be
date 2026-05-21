<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminReviewEventRequest;
use App\Http\Requests\AdminUpdateEventHomepageRequest;
use App\Http\Requests\AdminUpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class AdminEventController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return $this->paginateEvents($request);
    }

    public function pending(Request $request): AnonymousResourceCollection
    {
        return $this->paginateEvents($request, 'pending');
    }

    public function show(Event $event): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new EventResource($this->loadAdminEventData($event)),
        ]);
    }

    public function update(AdminUpdateEventRequest $request, Event $event): JsonResponse
    {
        $event->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Event updated successfully.',
            'data' => new EventResource($this->loadAdminEventData($event->refresh())),
        ]);
    }

    public function review(AdminReviewEventRequest $request, Event $event): JsonResponse
    {
        if ($event->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending events can be reviewed from this endpoint.',
            ], 422);
        }

        $event->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Event review status updated successfully.',
            'data' => new EventResource($this->loadAdminEventData($event->refresh())),
        ]);
    }

    public function homepage(AdminUpdateEventHomepageRequest $request, Event $event): JsonResponse
    {
        $event->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Event homepage settings updated successfully.',
            'data' => new EventResource($this->loadAdminEventData($event->refresh())),
        ]);
    }

    private function paginateEvents(Request $request, ?string $forcedStatus = null): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'status' => ['sometimes', 'required', Rule::in(['pending', 'approved', 'rejected'])],
            'category' => ['sometimes', 'required', 'string'],
            'is_featured' => ['sometimes', 'boolean'],
            'is_special' => ['sometimes', 'boolean'],
            'search' => ['sometimes', 'required', 'string', 'max:255'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $events = Event::query()
            ->with('organizer')
            ->withCount([
                'zones',
                'seats',
                'seats as available_seats_count' => fn ($query) => $query->where('status', 'available'),
            ])
            ->when($forcedStatus !== null, fn ($query) => $query->where('status', $forcedStatus))
            ->when($forcedStatus === null && isset($validated['status']), fn ($query) => $query->where('status', $validated['status']))
            ->when(isset($validated['category']), fn ($query) => $query->where('category', $validated['category']))
            ->when($request->has('is_featured'), fn ($query) => $query->where('is_featured', $request->boolean('is_featured')))
            ->when($request->has('is_special'), fn ($query) => $query->where('is_special', $request->boolean('is_special')))
            ->when(isset($validated['search']), function ($query) use ($validated): void {
                $query->where(function ($query) use ($validated): void {
                    $query->where('name', 'like', "%{$validated['search']}%")
                        ->orWhere('venue', 'like', "%{$validated['search']}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate((int) ($validated['per_page'] ?? 12));

        return EventResource::collection($events);
    }

    private function loadAdminEventData(Event $event): Event
    {
        return $event->load('organizer')->loadCount([
            'zones',
            'seats',
            'seats as available_seats_count' => fn ($query) => $query->where('status', 'available'),
        ]);
    }
}
