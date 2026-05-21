<?php

namespace App\Services;

use App\Exceptions\ApiProblemException;
use App\Models\Event;
use App\Models\Seat;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SeatLockService
{
    private const LOCK_MINUTES = 10;

    public function __construct(
        private readonly WaitingRoomService $waitingRoomService,
        private readonly SeatRealtimeService $seatRealtimeService,
        private readonly EventSeatCounterService $seatCounter,
    ) {}

    public function lockMinutes(): int
    {
        return self::LOCK_MINUTES;
    }

    public function lockSeats(Event $event, User $customer, array $seatIds): Collection
    {
        $this->waitingRoomService->ensureActiveAccess($event, $customer);
        $this->ensureEventSaleWindowIsOpen($event);
        $this->releaseExpiredLocks($event);
        $event->refresh();

        if ($event->isSoldOut()) {
            throw new ApiProblemException('Ticket sale is not currently open for this event.', 422);
        }

        $seats = DB::transaction(function () use ($event, $customer, $seatIds): Collection {
            $seats = $this->queryEventSeats($event, $seatIds)
                ->lockForUpdate()
                ->get();

            $this->ensureAllSeatsBelongToEvent($seats, $seatIds);

            $lockedUntil = now()->subMinutes(self::LOCK_MINUTES);

            foreach ($seats as $seat) {
                if ($seat->status === 'sold') {
                    throw new ApiProblemException('One or more selected seats are already sold.', 409);
                }

                $isLockedByAnotherCustomer = $seat->status === 'locked'
                    && (int) $seat->locked_by !== (int) $customer->id
                    && $seat->locked_at
                    && $seat->locked_at->gt($lockedUntil);

                if ($isLockedByAnotherCustomer) {
                    throw new ApiProblemException('One or more selected seats are already locked.', 409);
                }
            }

            $availableSeatsCount = $seats->where('status', 'available')->count();

            Seat::query()
                ->whereIn('id', $seats->pluck('id'))
                ->update([
                    'status' => 'locked',
                    'locked_by' => $customer->id,
                    'locked_at' => now(),
                ]);

            $this->seatCounter->decrementAvailable($event, $availableSeatsCount);

            return $this->queryEventSeats($event, $seatIds)->get();
        });

        $this->seatRealtimeService->broadcastSeats($seats);

        return $seats;
    }

    public function releaseSeats(Event $event, User $customer, array $seatIds): Collection
    {
        $seats = DB::transaction(function () use ($event, $customer, $seatIds): Collection {
            $seats = $this->queryEventSeats($event, $seatIds)
                ->where('locked_by', $customer->id)
                ->where('status', 'locked')
                ->lockForUpdate()
                ->get();

            Seat::query()
                ->whereIn('id', $seats->pluck('id'))
                ->update([
                    'status' => 'available',
                    'locked_by' => null,
                    'locked_at' => null,
                ]);

            $this->seatCounter->incrementAvailable($event, $seats->count());

            return $this->queryEventSeats($event, $seatIds)->get();
        });

        $this->seatRealtimeService->broadcastSeats($seats);

        return $seats;
    }

    public function releaseExpiredLocks(?Event $event = null): void
    {
        $seats = Seat::query()
            ->with('zone')
            ->when($event !== null, fn ($query) => $query->whereHas('zone', fn ($query) => $query->where('event_id', $event->id)))
            ->where('status', 'locked')
            ->where('locked_at', '<=', now()->subMinutes(self::LOCK_MINUTES))
            ->get();

        if ($seats->isEmpty()) {
            return;
        }

        Seat::query()
            ->whereIn('id', $seats->pluck('id'))
            ->update([
                'status' => 'available',
                'locked_by' => null,
                'locked_at' => null,
            ]);

        $this->seatCounter->incrementAvailableForSeats($seats);

        $this->seatRealtimeService->broadcastSeats(
            Seat::query()
                ->with('zone')
                ->whereIn('id', $seats->pluck('id'))
                ->get()
        );
    }

    public function ensureEventCanSellTickets(Event $event): void
    {
        $this->ensureEventSaleWindowIsOpen($event);

        if ($event->isSoldOut()) {
            throw new ApiProblemException('Ticket sale is not currently open for this event.', 422);
        }
    }

    public function ensureEventSaleWindowIsOpen(Event $event): void
    {
        if ($event->status !== 'approved') {
            throw new ApiProblemException('Only approved events can sell tickets.', 422);
        }

        if ($event->ticket_sale_starts_at && now()->lt($event->ticket_sale_starts_at)) {
            throw new ApiProblemException('Ticket sale is not currently open for this event.', 422);
        }

        if ($event->ticket_sale_ends_at && now()->gt($event->ticket_sale_ends_at)) {
            throw new ApiProblemException('Ticket sale is not currently open for this event.', 422);
        }
    }

    private function queryEventSeats(Event $event, array $seatIds): Builder
    {
        return Seat::query()
            ->with('zone')
            ->whereIn('id', $seatIds)
            ->whereHas('zone', fn ($query) => $query->where('event_id', $event->id));
    }

    private function ensureAllSeatsBelongToEvent(Collection $seats, array $seatIds): void
    {
        if ($seats->count() !== count($seatIds)) {
            throw new ApiProblemException('One or more selected seats do not belong to this event.', 422);
        }
    }
}
