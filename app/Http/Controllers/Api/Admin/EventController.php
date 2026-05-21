<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EventIndexRequest;
use App\Http\Requests\Admin\ReviewEventRequest;
use App\Http\Requests\Admin\UpdateEventHomepageRequest;
use App\Http\Requests\Admin\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Repositories\Admin\EventRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EventController extends Controller
{
    public function __construct(
        private readonly EventRepository $events,
    ) {}

    public function index(AdminEventIndexRequest $request): AnonymousResourceCollection
    {
        return EventResource::collection($this->events->paginate($request->filters()));
    }

    public function pending(AdminEventIndexRequest $request): AnonymousResourceCollection
    {
        return EventResource::collection($this->events->paginate($request->filters(), 'pending'));
    }

    public function show(Event $event): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new EventResource($this->events->loadDetails($event)),
        ]);
    }

    public function update(AdminUpdateEventRequest $request, Event $event): JsonResponse
    {
        $event->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Event updated successfully.',
            'data' => new EventResource($this->events->loadDetails($event->refresh())),
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
            'data' => new EventResource($this->events->loadDetails($event->refresh())),
        ]);
    }

    public function homepage(AdminUpdateEventHomepageRequest $request, Event $event): JsonResponse
    {
        $event->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Event homepage settings updated successfully.',
            'data' => new EventResource($this->events->loadDetails($event->refresh())),
        ]);
    }
}
