<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminEventIndexRequest;
use App\Http\Requests\AdminReviewEventRequest;
use App\Http\Requests\AdminUpdateEventHomepageRequest;
use App\Http\Requests\AdminUpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Repositories\AdminEventRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminEventController extends Controller
{
    public function __construct(
        private readonly AdminEventRepository $events,
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
