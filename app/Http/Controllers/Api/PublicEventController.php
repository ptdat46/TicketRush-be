<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublicEventIndexRequest;
use App\Http\Resources\HomepageEventResource;
use App\Models\Event;
use App\Services\PublicEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

class PublicEventController extends Controller
{
    public function index(PublicEventIndexRequest $request, PublicEventService $publicEvents): JsonResponse
    {
        $payload = $publicEvents->cachedListPayload($request->filters());

        return response()->json([
            'success' => true,
            ...$payload,
        ])->setPublic()->setMaxAge(60);
    }

    public function categoriesList(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->categories(),
        ])->setPublic()->setMaxAge(3600);
    }

    public function show(Event $event): JsonResponse
    {
        if ($event->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Event not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new HomepageEventResource($event->load('organizer')),
        ]);
    }

    public function categories(): array
    {
        return [
            ['key' => 'music', 'name' => 'Nhạc sống', 'icon' => 'music'],
            ['key' => 'dj', 'name' => 'DJ / EDM', 'icon' => 'disc'],
            ['key' => 'theater', 'name' => 'Sân khấu & Nghệ thuật', 'icon' => 'theater'],
            ['key' => 'sport', 'name' => 'Thể thao', 'icon' => 'trophy'],
            ['key' => 'workshop', 'name' => 'Hội thảo & Workshop', 'icon' => 'users'],
            ['key' => 'conference', 'name' => 'Hội nghị', 'icon' => 'presentation'],
            ['key' => 'comedy', 'name' => 'Hài kịch', 'icon' => 'smile'],
            ['key' => 'family', 'name' => 'Gia đình', 'icon' => 'heart'],
            ['key' => 'other', 'name' => 'Khác', 'icon' => 'ticket'],
        ];
    }
}
