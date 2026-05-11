<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerTicketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tickets = Ticket::query()
            ->where('customer_id', $request->user()->id)
            ->with(['event', 'seat.zone'])
            ->latest()
            ->paginate((int) $request->integer('per_page', 12));

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
