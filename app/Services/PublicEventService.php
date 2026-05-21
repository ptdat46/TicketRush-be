<?php

namespace App\Services;

use App\Repositories\PublicEventRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PublicEventService
{
    public function __construct(
        private readonly PublicEventRepository $events,
    ) {}

    public function list(array $filters): Collection|LengthAwarePaginator
    {
        $query = $this->events->queryForHomepage($filters);

        if (isset($filters['limit'])) {
            return $query->limit((int) $filters['limit'])->get();
        }

        return $query->paginate((int) ($filters['per_page'] ?? 12));
    }
}
