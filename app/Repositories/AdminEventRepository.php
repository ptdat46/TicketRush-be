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
            ->with('organizer')
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
