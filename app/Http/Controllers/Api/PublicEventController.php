<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\HomepageEventResource;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicEventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $now = now();
        $oneMonthAgo = $now->copy()->subMonth();

        $query = Event::query()
            ->with('organizer')
            ->withCount(['seats as available_seats_count' => fn ($q) => $q->where('status', 'available')])
            ->where('status', 'approved')
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->string('category')))
            ->when($request->filled('q'), function ($query) use ($request): void {
                $keyword = $request->string('q');

                $query->where(function ($query) use ($keyword): void {
                    $query->where('name', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%")
                        ->orWhere('venue', 'like', "%{$keyword}%");
                });
            })
            ->when($request->filled('starts_after'), fn ($query) => $query->where('starts_at', '>=', $request->date('starts_after')))
            ->when($request->filled('starts_before'), fn ($query) => $query->where('starts_at', '<=', $request->date('starts_before')))
            ->when($request->filled('sale_starts_after'), fn ($query) => $query->where('ticket_sale_starts_at', '>=', $request->date('sale_starts_after')))
            ->when($request->filled('sale_starts_before'), fn ($query) => $query->where('ticket_sale_starts_at', '<=', $request->date('sale_starts_before')))
            ->when($request->filled('ticket_status'), function ($query) use ($request, $now): void {
                $status = $request->string('ticket_status')->toString();

                match ($status) {
                    'on_sale' => $query
                        ->where(fn ($q) => $q->whereNull('ticket_sale_starts_at')->orWhere('ticket_sale_starts_at', '<=', $now))
                        ->where(fn ($q) => $q->whereNull('ticket_sale_ends_at')->orWhere('ticket_sale_ends_at', '>=', $now))
                        ->whereHas('seats', fn ($q) => $q->where('status', 'available')),
                    'sold_out' => $query
                        ->where(fn ($q) => $q->whereNull('ticket_sale_starts_at')->orWhere('ticket_sale_starts_at', '<=', $now))
                        ->where(fn ($q) => $q->whereNull('ticket_sale_ends_at')->orWhere('ticket_sale_ends_at', '>=', $now))
                        ->whereHas('seats')
                        ->whereDoesntHave('seats', fn ($q) => $q->where('status', 'available')),
                    'not_started' => $query->where('ticket_sale_starts_at', '>', $now),
                    'ended' => $query->where('ticket_sale_ends_at', '<', $now),
                    default => null,
                };
            })
            ->when($request->filled('is_featured'), fn ($query) => $query->where('is_featured', true))
            ->when($request->filled('is_special'), fn ($query) => $query->where('is_special', true))
            ->when($request->filled('trending'), function ($query) use ($now, $oneMonthAgo): void {
                $query
                    ->where(fn ($q) => $q->whereNull('ticket_sale_starts_at')->orWhere('ticket_sale_starts_at', '<=', $now))
                    ->where(fn ($q) => $q->whereNull('ticket_sale_ends_at')->orWhere('ticket_sale_ends_at', '>=', $now))
                    ->whereHas('seats', fn ($q) => $q->where('status', 'available'))
                    ->withCount([
                        'tickets as tickets_sold_count' => fn ($q) => $q
                            ->where('status', 'valid')
                            ->where('created_at', '>=', $oneMonthAgo),
                    ])
                    ->orderByDesc('tickets_sold_count');
            })
            ->when(!$request->filled('trending'), function ($query): void {
                $query->orderByDesc('is_featured')
                    ->orderBy('sort_order')
                    ->orderBy('starts_at');
            });

        if ($request->filled('limit')) {
            $events = $query->limit((int) $request->integer('limit'))->get();

            return response()->json([
                'success' => true,
                'data' => HomepageEventResource::collection($events),
            ]);
        }

        $events = $query->paginate((int) $request->integer('per_page', 12));

        return response()->json([
            'success' => true,
            'data' => HomepageEventResource::collection($events),
            'meta' => [
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
            ],
        ]);
    }

    public function categoriesList(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->categories(),
        ]);
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
