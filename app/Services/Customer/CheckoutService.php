<?php

namespace App\Services\Customer;

use App\Exceptions\ApiProblemException;
use App\Models\Event;
use App\Models\Order;
use App\Models\Seat;
use App\Models\Ticket;
use App\Models\User;
use App\Services\SeatLockService;
use App\Services\SeatRealtimeService;
use App\Services\WaitingRoomService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutService
{
    public function __construct(
        private readonly SeatLockService $seatLockService,
        private readonly WaitingRoomService $waitingRoomService,
        private readonly SeatRealtimeService $seatRealtimeService,
    ) {}

    public function checkout(Event $event, User $customer, array $seatIds, string $paymentMethod = 'mock', ?string $paymentReference = null): Order
    {
        $this->waitingRoomService->ensureActiveAccess($event, $customer);
        $this->seatLockService->ensureEventSaleWindowIsOpen($event);
        $this->seatLockService->releaseExpiredLocks($event);

        $soldSeatIds = [];

        $order = DB::transaction(function () use ($event, $customer, $seatIds, $paymentMethod, $paymentReference, &$soldSeatIds): Order {
            $seats = Seat::query()
                ->with('zone')
                ->whereIn('id', $seatIds)
                ->whereHas('zone', fn ($query) => $query->where('event_id', $event->id))
                ->lockForUpdate()
                ->get();

            if ($seats->count() !== count($seatIds)) {
                throw new ApiProblemException('One or more selected seats do not belong to this event.', 422);
            }

            $lockCutoff = now()->subMinutes($this->seatLockService->lockMinutes());

            foreach ($seats as $seat) {
                $isOwnedActiveLock = $seat->status === 'locked'
                    && (int) $seat->locked_by === (int) $customer->id
                    && $seat->locked_at
                    && $seat->locked_at->gt($lockCutoff);

                if (! $isOwnedActiveLock) {
                    throw new ApiProblemException('Please lock all selected seats before checkout.', 409);
                }
            }

            $subtotal = $seats->sum(fn (Seat $seat): float => (float) $seat->zone->price);

            $order = Order::create([
                'order_code' => $this->makeUniqueCode('ORD', Order::class, 'order_code'),
                'customer_id' => $customer->id,
                'event_id' => $event->id,
                'subtotal_amount' => $subtotal,
                'total_amount' => $subtotal,
                'currency' => 'VND',
                'status' => 'paid',
                'payment_method' => $paymentMethod,
                'payment_reference' => $paymentReference ?: $this->makeReference(),
                'paid_at' => now(),
                'expires_at' => null,
            ]);

            foreach ($seats as $seat) {
                $seat->update([
                    'status' => 'sold',
                    'locked_by' => null,
                    'locked_at' => null,
                ]);
                $soldSeatIds[] = $seat->id;

                Ticket::create([
                    'ticket_code' => $this->makeUniqueCode('TICK', Ticket::class, 'ticket_code'),
                    'order_id' => $order->id,
                    'event_id' => $event->id,
                    'seat_id' => $seat->id,
                    'customer_id' => $customer->id,
                    'qr_code' => $this->makeUniqueCode('QR', Ticket::class, 'qr_code'),
                    'status' => 'valid',
                    'issued_at' => now(),
                ]);
            }

            return $order->load(['event', 'tickets.seat.zone'])->loadCount('tickets');
        });

        $this->seatRealtimeService->broadcastSeats(
            Seat::query()
                ->with('zone')
                ->whereIn('id', $soldSeatIds)
                ->get()
        );

        return $order;
    }

    private function makeReference(): string
    {
        return 'MOCK-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6));
    }

    private function makeUniqueCode(string $prefix, string $modelClass, string $column): string
    {
        do {
            $code = $prefix.'-'.now()->format('YmdHis').'-'.Str::upper(Str::random(8));
        } while ($modelClass::query()->where($column, $code)->exists());

        return $code;
    }
}
