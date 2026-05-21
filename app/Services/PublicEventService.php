<?php

namespace App\Services;

use App\Http\Resources\HomepageEventResource;
use App\Repositories\PublicEventRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class PublicEventService
{
    private const CACHE_TTL_SECONDS = 300;

    private const DEFAULT_PER_PAGE = 12;

    public function __construct(
        private readonly PublicEventRepository $events,
    ) {}

    /**
     * Build a cached, JSON-ready payload for the public event list endpoint.
     *
     * @return array{data: array, meta?: array}
     */
    public function homepagePayload(array $filters): array
    {
        return Cache::remember(
            $this->cacheKey($filters),
            self::CACHE_TTL_SECONDS,
            fn (): array => $this->buildPayload($filters),
        );
    }

    private function buildPayload(array $filters): array
    {
        $events = $this->fetch($filters);
        $payload = ['data' => HomepageEventResource::collection($events)->resolve()];

        if ($events instanceof LengthAwarePaginator) {
            $payload['meta'] = [
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
            ];
        }

        return $payload;
    }

    private function fetch(array $filters): Collection|LengthAwarePaginator
    {
        $query = $this->events->queryForHomepage($filters);

        if (isset($filters['limit'])) {
            return $query->limit((int) $filters['limit'])->get();
        }

        return $query->paginate((int) ($filters['per_page'] ?? self::DEFAULT_PER_PAGE));
    }

    private function cacheKey(array $filters): string
    {
        ksort($filters);

        return 'public_events:' . md5(json_encode($filters));
    }
}
