<?php

namespace App\Services;

use App\Events\EventSeatStatusUpdated;
use App\Models\Seat;
use Illuminate\Support\Collection;

class SeatRealtimeService
{
    public function broadcastSeats(iterable $seats): void
    {
        $seatCollection = collect($seats)
            ->filter(fn (mixed $seat): bool => $seat instanceof Seat)
            ->values();

        if ($seatCollection->isEmpty()) {
            return;
        }

        $seatCollection->each(fn (Seat $seat) => $seat->loadMissing('zone'));

        $seatCollection
            ->groupBy(fn (Seat $seat): int => (int) $seat->zone->event_id)
            ->each(function (Collection $eventSeats, int $eventId): void {
                event(new EventSeatStatusUpdated($eventId, [
                    'event_id' => $eventId,
                    'seats' => $eventSeats->map(fn (Seat $seat): array => [
                        'id' => $seat->id,
                        'zone_id' => $seat->zone_id,
                        'row_index' => $seat->row_index,
                        'col_index' => $seat->col_index,
                        'status' => $seat->status,
                        'locked_at' => $seat->locked_at?->toIso8601String(),
                        'locked_until' => $seat->locked_at?->copy()->addMinutes(10)->toIso8601String(),
                        'updated_at' => $seat->updated_at?->toIso8601String(),
                    ])->values()->all(),
                    'updated_at' => now()->toIso8601String(),
                ]));
            });
    }
}
