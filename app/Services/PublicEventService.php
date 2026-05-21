<?php

namespace App\Services;

use App\Http\Resources\HomepageEventResource;
use App\Repositories\PublicEventRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class PublicEventService
{
    private const CACHE_TTL = 300;

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

    public function cachedListPayload(array $filters): array
    {
        $key = 'public_events:' . md5(json_encode($filters));

        return Cache::remember($key, self::CACHE_TTL, function () use ($filters): array {
            $events = $this->list($filters);

            if ($events instanceof LengthAwarePaginator) {
                return [
                    'data' => HomepageEventResource::collection($events)->resolve(),
                    'meta' => [
                        'current_page' => $events->currentPage(),
                        'last_page' => $events->lastPage(),
                        'per_page' => $events->perPage(),
                        'total' => $events->total(),
                    ],
                ];
            }

            return [
                'data' => HomepageEventResource::collection($events)->resolve(),
            ];
        });
    }
}
