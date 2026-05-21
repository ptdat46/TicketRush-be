<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublicEventIndexRequest;
use App\Http\Resources\HomepageEventResource;
use App\Models\Event;
use App\Services\PublicEventService;
use Illuminate\Http\JsonResponse;

class PublicEventController extends Controller
{
    private const LIST_CACHE_SECONDS = 60;

    private const CATEGORIES_CACHE_SECONDS = 3600;

    public function __construct(
        private readonly PublicEventService $publicEvents,
    ) {}

    public function index(PublicEventIndexRequest $request): JsonResponse
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
