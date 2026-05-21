<?php

namespace App\Services\Customer;

use App\Models\Event;
use App\Models\User;
use App\Services\SeatLockService;
use App\Services\WaitingRoomService;

class SeatMapService
{
    public function __construct(
        private readonly SeatLockService $seatLockService,
        private readonly WaitingRoomService $waitingRoomService,
    ) {}

    public function getMap(Event $event, User $customer): array
    {
        $this->waitingRoomService->ensureActiveAccess($event, $customer);
        $this->seatLockService->ensureEventSaleWindowIsOpen($event);
        $this->seatLockService->releaseExpiredLocks($event);

        $event->load([
            'zones' => fn ($query) => $query
                ->select([
                    'id',
                    'event_id',
                    'name',
                    'price',
                    'color',
                    'icon_url',
                    'pos_x',
                    'pos_y',
                    'width',
                    'length',
                    'is_seating',
                ])
                ->orderBy('pos_y')
                ->orderBy('pos_x')
                ->orderBy('id'),
            'zones.seats' => fn ($query) => $query
                ->select([
                    'id',
                    'zone_id',
                    'row_index',
                    'col_index',
                    'status',
                    'locked_by',
                    'locked_at',
                    'updated_at',
                ])
                ->orderBy('row_index')
                ->orderBy('col_index')
                ->orderBy('id'),
        ]);

        return [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'display_type' => $event->display_type,
                'master_width' => $event->master_width,
                'master_length' => $event->master_length,
                'total_seats' => $event->total_seats,
                'available_seats_count' => $event->available_seats_count,
                'ticket_sale_status' => $event->ticketSaleStatus(),
            ],
            'lock_minutes' => $this->seatLockService->lockMinutes(),
            'max_selectable_seats' => 10,
            'zones' => $event->zones->map(fn ($zone): array => [
                'id' => $zone->id,
                'name' => $zone->name,
                'price' => $zone->price,
                'color' => $zone->color,
                'icon_url' => $zone->icon_url,
                'pos_x' => $zone->pos_x,
                'pos_y' => $zone->pos_y,
                'width' => $zone->width,
                'length' => $zone->length,
                'is_seating' => (bool) $zone->is_seating,
                'seats' => $zone->seats->map(fn ($seat): array => [
                    'id' => $seat->id,
                    'row_index' => $seat->row_index,
                    'col_index' => $seat->col_index,
                    'status' => $seat->status,
                    'is_locked_by_me' => $seat->status === 'locked'
                        && (int) $seat->locked_by === (int) $customer->id,
                    'locked_until' => $seat->locked_at?->copy()
                        ->addMinutes($this->seatLockService->lockMinutes())
                        ->toIso8601String(),
                    'updated_at' => $seat->updated_at?->toIso8601String(),
                ])->values()->all(),
            ])->values()->all(),
        ];
    }
}
