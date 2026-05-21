<?php

namespace App\Repositories\Public;

use App\Models\Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class EventRepository
{
    public function queryForHomepage(array $filters): Builder
    {
        $now = now();
        $oneMonthAgo = $now->copy()->subMonth();

        return Event::query()
            ->select([
                'id',
                'organizer_id',
                'name',
                'description',
                'category',
                'thumbnail_url',
                'banner_url',
                'venue',
                'starts_at',
                'ends_at',
                'ticket_sale_starts_at',
                'ticket_sale_ends_at',
                'display_type',
                'total_seats',
                'available_seats_count',
                'is_featured',
                'is_special',
                'sort_order',
                'status',
                'created_at',
                'updated_at',
            ])
            ->with(['organizer:id,name,organizer_name'])
            ->where('status', 'approved')
            ->when(isset($filters['category']), fn (Builder $query) => $query->where('category', $filters['category']))
            ->when(isset($filters['q']), fn (Builder $query) => $this->search($query, $filters['q']))
            ->when(isset($filters['starts_after']), fn (Builder $query) => $query->where('starts_at', '>=', $filters['starts_after']))
            ->when(isset($filters['starts_before']), fn (Builder $query) => $query->where('starts_at', '<=', $filters['starts_before']))
            ->when(isset($filters['sale_starts_after']), fn (Builder $query) => $query->where('ticket_sale_starts_at', '>=', $filters['sale_starts_after']))
            ->when(isset($filters['sale_starts_before']), fn (Builder $query) => $query->where('ticket_sale_starts_at', '<=', $filters['sale_starts_before']))
            ->when(isset($filters['ticket_status']), fn (Builder $query) => $this->filterTicketStatus($query, $filters['ticket_status'], $now))
            ->when(array_key_exists('is_featured', $filters), fn (Builder $query) => $query->where('is_featured', $filters['is_featured']))
            ->when(array_key_exists('is_special', $filters), fn (Builder $query) => $query->where('is_special', $filters['is_special']))
            ->when(! empty($filters['trending']), fn (Builder $query) => $this->trending($query, $now, $oneMonthAgo))
            ->when(empty($filters['trending']), fn (Builder $query) => $this->defaultSort($query));
    }

    private function search(Builder $query, string $keyword): void
    {
        $query->where(function (Builder $query) use ($keyword): void {
            $query->where('name', 'like', "%{$keyword}%")
                ->orWhere('description', 'like', "%{$keyword}%")
                ->orWhere('venue', 'like', "%{$keyword}%");
        });
    }

    private function filterTicketStatus(Builder $query, string $status, Carbon $now): void
    {
        match ($status) {
            'on_sale' => $query
                ->where(fn (Builder $query) => $query->whereNull('ticket_sale_starts_at')->orWhere('ticket_sale_starts_at', '<=', $now))
                ->where(fn (Builder $query) => $query->whereNull('ticket_sale_ends_at')->orWhere('ticket_sale_ends_at', '>=', $now))
                ->where('available_seats_count', '>', 0),
            'sold_out' => $query
                ->where(fn (Builder $query) => $query->whereNull('ticket_sale_starts_at')->orWhere('ticket_sale_starts_at', '<=', $now))
                ->where(fn (Builder $query) => $query->whereNull('ticket_sale_ends_at')->orWhere('ticket_sale_ends_at', '>=', $now))
                ->where('total_seats', '>', 0)
                ->where('available_seats_count', 0),
            'not_started' => $query->where('ticket_sale_starts_at', '>', $now),
            'ended' => $query->where('ticket_sale_ends_at', '<', $now),
            default => null,
        };
    }

    private function trending(Builder $query, Carbon $now, Carbon $oneMonthAgo): void
    {
        $query
            ->where(fn (Builder $query) => $query->whereNull('ticket_sale_starts_at')->orWhere('ticket_sale_starts_at', '<=', $now))
            ->where(fn (Builder $query) => $query->whereNull('ticket_sale_ends_at')->orWhere('ticket_sale_ends_at', '>=', $now))
            ->where('available_seats_count', '>', 0)
            ->withCount([
                'tickets as tickets_sold_count' => fn (Builder $query) => $query
                    ->where('status', 'valid')
                    ->where('created_at', '>=', $oneMonthAgo),
            ])
            ->orderByDesc('tickets_sold_count');
    }

    private function defaultSort(Builder $query): void
    {
        $query->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('starts_at');
    }
}
