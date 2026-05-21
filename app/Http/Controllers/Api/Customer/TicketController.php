<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\TicketIndexRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Repositories\Customer\TicketRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function __construct(
        private readonly TicketRepository $tickets,
    ) {}

    public function index(TicketIndexRequest $request): JsonResponse
    {
        $tickets = $this->tickets->paginateForCustomer($request->user(), $request->filters());

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
