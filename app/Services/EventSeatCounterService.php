<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Seat;
use Illuminate\Support\Collection;

class EventSeatCounterService
{
    public function sync(Event $event): void
    {
        $event->forceFill([
            'total_seats' => $event->seats()->count(),
            'available_seats_count' => $event->seats()->where('status', 'available')->count(),
        ])->save();
    }

    public function decrementAvailable(Event $event, int $count): void
    {
        if ($count < 1) {
            return;
        }

        Event::query()
            ->whereKey($event->id)
            ->decrement('available_seats_count', $count);
    }

    public function incrementAvailable(Event $event, int $count): void
    {
        if ($count < 1) {
            return;
        }

        Event::query()
            ->whereKey($event->id)
            ->increment('available_seats_count', $count);
    }

    public function incrementAvailableForSeats(Collection $seats): void
    {
        $seats
            ->filter(fn (Seat $seat): bool => $seat->relationLoaded('zone'))
            ->groupBy(fn (Seat $seat): int => (int) $seat->zone->event_id)
            ->each(function (Collection $eventSeats, int $eventId): void {
                Event::query()
                    ->whereKey($eventId)
                    ->increment('available_seats_count', $eventSeats->count());
            });
    }
}
