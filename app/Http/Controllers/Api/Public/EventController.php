<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\EventIndexRequest;
use App\Http\Resources\HomepageEventResource;
use App\Models\Event;
use App\Services\Public\EventService;
use Illuminate\Http\JsonResponse;

class EventController extends Controller
{
    private const LIST_CACHE_SECONDS = 60;

    private const CATEGORIES_CACHE_SECONDS = 3600;

    public function __construct(
        private readonly EventService $publicEvents,
    ) {}

    public function index(EventIndexRequest $request): JsonResponse
    {
        $payload = $this->publicEvents->homepagePayload($request->filters());

        return $this->success($payload, self::LIST_CACHE_SECONDS);
    }

    public function show(Event $event): JsonResponse
    {
        if ($event->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Event not found.',
            ], 404);
        }

        return $this->success([
            'data' => new HomepageEventResource($event->load('organizer')),
        ]);
    }

    public function categoriesList(): JsonResponse
    {
        return $this->success([
            'data' => config('event_categories'),
        ], self::CATEGORIES_CACHE_SECONDS);
    }

    private function success(array $payload, ?int $cacheSeconds = null): JsonResponse
    {
        $response = response()->json(['success' => true, ...$payload]);

        if ($cacheSeconds !== null) {
            $response->setPublic()->setMaxAge($cacheSeconds);
        }

        return $response;
    }
}
