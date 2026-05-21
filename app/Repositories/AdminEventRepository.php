<?php

namespace App\Repositories;

use App\Models\Event;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AdminEventRepository
{
    public function paginate(array $filters, ?string $forcedStatus = null): LengthAwarePaginator
    {
        return Event::query()
            ->select([
                'id',
                'organizer_id',
                'name',
                'description',
                'category',
                'thumbnail_url',
                'banner_url',
                'is_featured',
                'is_special',
                'sort_order',
                'venue',
                'starts_at',
                'ends_at',
                'status',
                'display_type',
                'master_width',
                'master_length',
                'total_seats',
                'available_seats_count',
                'ticket_sale_starts_at',
                'ticket_sale_ends_at',
                'bank_name',
                'bank_account_number',
                'bank_account_name',
                'created_at',
                'updated_at',
            ])
            ->with(['organizer:id,name,organizer_name'])
            ->withCount('zones')
            ->when($forcedStatus !== null, fn (Builder $query) => $query->where('status', $forcedStatus))
            ->when($forcedStatus === null && isset($filters['status']), fn (Builder $query) => $query->where('status', $filters['status']))
            ->when(isset($filters['category']), fn (Builder $query) => $query->where('category', $filters['category']))
            ->when(array_key_exists('is_featured', $filters), fn (Builder $query) => $query->where('is_featured', $filters['is_featured']))
            ->when(array_key_exists('is_special', $filters), fn (Builder $query) => $query->where('is_special', $filters['is_special']))
            ->when(isset($filters['search']), fn (Builder $query) => $this->search($query, $filters['search']))
            ->orderByDesc('created_at')
            ->paginate((int) ($filters['per_page'] ?? 12));
    }

    public function loadDetails(Event $event): Event
    {
        return $event->load('organizer')->loadCount('zones');
    }

    private function search(Builder $query, string $keyword): void
    {
        $query->where(function (Builder $query) use ($keyword): void {
            $query->where('name', 'like', "%{$keyword}%")
                ->orWhere('venue', 'like', "%{$keyword}%");
        });
    }
}
