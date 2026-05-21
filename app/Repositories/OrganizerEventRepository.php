<?php

namespace App\Repositories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class OrganizerEventRepository
{
    public function paginateForOrganizer(User $organizer, array $filters): LengthAwarePaginator
    {
        return Event::query()
            ->where('organizer_id', $organizer->id)
            ->when(isset($filters['status']), fn (Builder $query) => $query->where('status', $filters['status']))
            ->when(isset($filters['category']), fn (Builder $query) => $query->where('category', $filters['category']))
            ->when(isset($filters['starts_after']), fn (Builder $query) => $query->where('starts_at', '>=', $filters['starts_after']))
            ->when(isset($filters['starts_before']), fn (Builder $query) => $query->where('starts_at', '<=', $filters['starts_before']))
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 12));
    }
}
