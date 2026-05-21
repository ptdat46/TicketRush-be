<?php

namespace App\Repositories\Customer;

use App\Models\Event;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class TicketRepository
{
    public function paginateForCustomer(User $customer, array $filters): LengthAwarePaginator
    {
        $sortBy = $filters['sort_by'] ?? 'issued_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        return Ticket::query()
            ->select([
                'id',
                'ticket_code',
                'qr_code',
                'order_id',
                'event_id',
                'seat_id',
                'customer_id',
                'status',
                'issued_at',
                'checked_in_at',
                'created_at',
            ])
            ->where('customer_id', $customer->id)
            ->with([
                'event:id,name,thumbnail_url,starts_at,ends_at,venue',
                'seat:id,zone_id,row_index,col_index',
                'seat.zone:id,name,price',
            ])
            ->when(isset($filters['status']), fn (Builder $query) => $this->filterDisplayStatus($query, $filters['status']))
            ->when($sortBy === 'event_starts_at', fn (Builder $query) => $query->orderBy(
                Event::select('starts_at')->whereColumn('events.id', 'tickets.event_id'),
                $sortDirection,
            ))
            ->when($sortBy !== 'event_starts_at', fn (Builder $query) => $query->orderBy($sortBy, $sortDirection))
            ->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 12));
    }

    private function filterDisplayStatus(Builder $query, string $status): void
    {
        match ($status) {
            'valid' => $query
                ->where('status', 'valid')
                ->whereHas('event', fn (Builder $query) => $query
                    ->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now())),
            'expired' => $query
                ->where('status', 'valid')
                ->whereHas('event', fn (Builder $query) => $query->where('ends_at', '<', now())),
            default => $query->where('status', $status),
        };
    }
}
