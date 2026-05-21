<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TicketResource;
use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerTicketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['sometimes', 'required', Rule::in(['valid', 'used', 'expired', 'void'])],
            'sort_by' => ['sometimes', 'required', Rule::in(['issued_at', 'event_starts_at', 'created_at', 'status'])],
            'sort_direction' => ['sometimes', 'required', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $sortBy = $validated['sort_by'] ?? 'issued_at';
        $sortDirection = $validated['sort_direction'] ?? 'desc';

        $tickets = Ticket::query()
            ->where('customer_id', $request->user()->id)
            ->with(['event', 'seat.zone'])
            ->when(isset($validated['status']), function ($query) use ($validated): void {
                match ($validated['status']) {
                    'valid' => $query
                        ->where('status', 'valid')
                        ->whereHas('event', fn ($query) => $query
                            ->whereNull('ends_at')
                            ->orWhere('ends_at', '>=', now())),
                    'expired' => $query
                        ->where('status', 'valid')
                        ->whereHas('event', fn ($query) => $query->where('ends_at', '<', now())),
                    default => $query->where('status', $validated['status']),
                };
            })
            ->when($sortBy === 'event_starts_at', fn ($query) => $query->orderBy(
                Event::select('starts_at')->whereColumn('events.id', 'tickets.event_id'),
                $sortDirection,
            ))
            ->when($sortBy !== 'event_starts_at', fn ($query) => $query->orderBy($sortBy, $sortDirection))
            ->orderByDesc('id')
            ->paginate((int) ($validated['per_page'] ?? 12));

        return response()->json([
            'success' => true,
            'data' => TicketResource::collection($tickets),
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
            ],
        ]);
    }

    public function show(Request $request, Ticket $ticket): JsonResponse
    {
        if ((int) $ticket->customer_id !== (int) $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view this ticket.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new TicketResource($ticket->load(['event', 'seat.zone', 'order'])),
        ]);
    }
}
