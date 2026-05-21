<?php

namespace App\Repositories\Organizer;

use App\Models\Event;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class EventRepository
{
    public function paginateForOrganizer(User $organizer, array $filters): LengthAwarePaginator
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
            ->where('organizer_id', $organizer->id)
            ->when(isset($filters['status']), fn (Builder $query) => $query->where('status', $filters['status']))
            ->when(isset($filters['category']), fn (Builder $query) => $query->where('category', $filters['category']))
            ->when(isset($filters['starts_after']), fn (Builder $query) => $query->where('starts_at', '>=', $filters['starts_after']))
            ->when(isset($filters['starts_before']), fn (Builder $query) => $query->where('starts_at', '<=', $filters['starts_before']))
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 12));
    }
}
