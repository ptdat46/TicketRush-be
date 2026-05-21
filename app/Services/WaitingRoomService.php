<?php

namespace App\Services;

use App\Events\EventWaitingRoomEntryUpdated;
use App\Events\EventWaitingRoomSummaryUpdated;
use App\Exceptions\ApiProblemException;
use App\Models\Event;
use App\Models\EventWaitingRoomEntry;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class WaitingRoomService
{
    private const STATUS_ACTIVE = 'active';

    private const STATUS_WAITING = 'waiting';

    private const STATUS_LEFT = 'left';

    private const STATUS_EXPIRED = 'expired';

    private const ACTIVE_STATUSES = [self::STATUS_ACTIVE, self::STATUS_WAITING];

    private const ENTRY_TTL_SECONDS = 120;

    private const POLL_AFTER_SECONDS = 5;

    private const ESTIMATED_SECONDS_PER_PERSON = 2;

    public function join(Event $event, User $customer): array
    {
        $this->ensureEventCanUseWaitingRoom($event);

        $promotedPayloads = [];

        $payload = DB::transaction(function () use ($event, $customer, &$promotedPayloads): array {
            $lockedEvent = $this->lockEvent($event);
            $now = now();

            $this->expireStaleEntries($lockedEvent, $now);
            $promotedPayloads = [
                ...$promotedPayloads,
                ...$this->promoteWaitingEntries($lockedEvent, $now),
            ];

            $entry = $this->customerEntryForUpdate($lockedEvent, $customer);

            if (! $entry || ! in_array($entry->status, self::ACTIVE_STATUSES, true)) {
                $status = $this->hasAvailableSlot($lockedEvent) && ! $this->hasWaitingEntries($lockedEvent)
                    ? self::STATUS_ACTIVE
                    : self::STATUS_WAITING;

                $entry = EventWaitingRoomEntry::query()->updateOrCreate(
                    [
                        'event_id' => $lockedEvent->id,
                        'customer_id' => $customer->id,
                    ],
                    [
                        'status' => $status,
                        'joined_at' => $now,
                        'admitted_at' => $status === self::STATUS_ACTIVE ? $now : null,
                        'last_seen_at' => $now,
                        'left_at' => null,
                    ],
                );
            } else {
                $entry->update(['last_seen_at' => $now]);
            }

            $promotedPayloads = [
                ...$promotedPayloads,
                ...$this->promoteWaitingEntries($lockedEvent, $now),
            ];

            return $this->makePayload($entry->refresh());
        });

        $this->broadcastWaitingRoomUpdates($event, $payload, $promotedPayloads);

        return $payload;
    }

    public function status(Event $event, User $customer): array
    {
        $this->ensureEventCanUseWaitingRoom($event);

        $promotedPayloads = [];

        $payload = DB::transaction(function () use ($event, $customer, &$promotedPayloads): array {
            $lockedEvent = $this->lockEvent($event);
            $now = now();

            $this->expireStaleEntries($lockedEvent, $now);
            $promotedPayloads = [
                ...$promotedPayloads,
                ...$this->promoteWaitingEntries($lockedEvent, $now),
            ];

            $entry = $this->customerEntryForUpdate($lockedEvent, $customer);

            if (! $entry) {
                throw new ApiProblemException('Please join the waiting room before checking status.', 404);
            }

            if (in_array($entry->status, self::ACTIVE_STATUSES, true)) {
                $entry->update(['last_seen_at' => $now]);
            }

            $promotedPayloads = [
                ...$promotedPayloads,
                ...$this->promoteWaitingEntries($lockedEvent, $now),
            ];

            return $this->makePayload($entry->refresh());
        });

        $this->broadcastWaitingRoomUpdates($event, $payload, $promotedPayloads);

        return $payload;
    }

    public function leave(Event $event, User $customer): array
    {
        $promotedPayloads = [];

        $payload = DB::transaction(function () use ($event, $customer, &$promotedPayloads): array {
            $lockedEvent = $this->lockEvent($event);
            $now = now();

            $entry = $this->customerEntryForUpdate($lockedEvent, $customer);

            if (! $entry) {
                throw new ApiProblemException('Waiting room entry not found.', 404);
            }

            $entry->update([
                'status' => self::STATUS_LEFT,
                'left_at' => $now,
                'last_seen_at' => $now,
            ]);

            $promotedPayloads = $this->promoteWaitingEntries($lockedEvent, $now);

            return $this->makePayload($entry->refresh());
        });

        $this->broadcastWaitingRoomUpdates($event, $payload, $promotedPayloads);

        return $payload;
    }

    public function ensureActiveAccess(Event $event, User $customer): void
    {
        $now = now();
        $entry = EventWaitingRoomEntry::query()
            ->where('event_id', $event->id)
            ->where('customer_id', $customer->id)
            ->where('status', self::STATUS_ACTIVE)
            ->first();

        if (! $entry || ! $entry->last_seen_at || $entry->last_seen_at->lt($now->copy()->subSeconds(self::ENTRY_TTL_SECONDS))) {
            throw new ApiProblemException('Please wait until your queue turn before booking this event.', 409);
        }

        $entry->update(['last_seen_at' => $now]);
    }

    private function ensureEventCanUseWaitingRoom(Event $event): void
    {
        if ($event->status !== 'approved') {
            throw new ApiProblemException('Only approved events can open a waiting room.', 422);
        }

        if ($event->ticket_sale_starts_at && now()->lt($event->ticket_sale_starts_at)) {
            throw new ApiProblemException('Ticket sale is not currently open for this event.', 422);
        }

        if ($event->ticket_sale_ends_at && now()->gt($event->ticket_sale_ends_at)) {
            throw new ApiProblemException('Ticket sale is not currently open for this event.', 422);
        }

        if ($this->capacity($event) < 1) {
            throw new ApiProblemException('No sellable seats are configured for this event.', 422);
        }
    }

    private function lockEvent(Event $event): Event
    {
        return Event::query()
            ->whereKey($event->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function expireStaleEntries(Event $event, Carbon $now): void
    {
        EventWaitingRoomEntry::query()
            ->where('event_id', $event->id)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->where('last_seen_at', '<', $now->copy()->subSeconds(self::ENTRY_TTL_SECONDS))
            ->update([
                'status' => self::STATUS_EXPIRED,
                'left_at' => $now,
            ]);
    }

    private function promoteWaitingEntries(Event $event, Carbon $now): array
    {
        $slots = $this->capacity($event) - $this->activeCount($event);

        if ($slots < 1) {
            return [];
        }

        $entries = EventWaitingRoomEntry::query()
            ->where('event_id', $event->id)
            ->where('status', self::STATUS_WAITING)
            ->where('last_seen_at', '>=', $now->copy()->subSeconds(self::ENTRY_TTL_SECONDS))
            ->orderBy('joined_at')
            ->orderBy('id')
            ->limit($slots)
            ->lockForUpdate()
            ->get();

        foreach ($entries as $entry) {
            $entry->update([
                'status' => self::STATUS_ACTIVE,
                'admitted_at' => $now,
                'last_seen_at' => $now,
            ]);
        }

        return $entries
            ->map(fn (EventWaitingRoomEntry $entry): array => $this->makePayload($entry->refresh()))
            ->all();
    }

    private function makePayload(EventWaitingRoomEntry $entry): array
    {
        $event = $entry->event()->firstOrFail();
        $position = $entry->status === self::STATUS_WAITING ? $this->waitingPosition($entry) : null;
        $peopleAhead = $position !== null ? max(0, $position - 1) : 0;

        return [
            'id' => $entry->id,
            'event_id' => $entry->event_id,
            'customer_id' => $entry->customer_id,
            'status' => $entry->status,
            'can_enter_booking' => $entry->status === self::STATUS_ACTIVE,
            'position' => $position,
            'people_ahead' => $peopleAhead,
            'waiting_count' => $this->waitingCount($event),
            'active_count' => $this->activeCount($event),
            'capacity' => $this->capacity($event),
            'estimated_wait_seconds' => $position !== null ? $peopleAhead * self::ESTIMATED_SECONDS_PER_PERSON : 0,
            'poll_after_seconds' => self::POLL_AFTER_SECONDS,
            'heartbeat_ttl_seconds' => self::ENTRY_TTL_SECONDS,
            'joined_at' => $entry->joined_at?->toIso8601String(),
            'admitted_at' => $entry->admitted_at?->toIso8601String(),
            'last_seen_at' => $entry->last_seen_at?->toIso8601String(),
        ];
    }

    private function waitingPosition(EventWaitingRoomEntry $entry): int
    {
        return EventWaitingRoomEntry::query()
            ->where('event_id', $entry->event_id)
            ->where('status', self::STATUS_WAITING)
            ->where(function ($query) use ($entry): void {
                $query->where('joined_at', '<', $entry->joined_at)
                    ->orWhere(function ($query) use ($entry): void {
                        $query->where('joined_at', $entry->joined_at)
                            ->where('id', '<=', $entry->id);
                    });
            })
            ->count();
    }

    private function hasAvailableSlot(Event $event): bool
    {
        return $this->activeCount($event) < $this->capacity($event);
    }

    private function hasWaitingEntries(Event $event): bool
    {
        return EventWaitingRoomEntry::query()
            ->where('event_id', $event->id)
            ->where('status', self::STATUS_WAITING)
            ->exists();
    }

    private function activeCount(Event $event): int
    {
        return EventWaitingRoomEntry::query()
            ->where('event_id', $event->id)
            ->where('status', self::STATUS_ACTIVE)
            ->count();
    }

    private function broadcastWaitingRoomUpdates(Event $event, array $payload, array $promotedPayloads = []): void
    {
        $summary = $this->makeSummaryPayload($event);

        event(new EventWaitingRoomSummaryUpdated((int) $event->id, $summary));

        collect([$payload, ...$promotedPayloads])
            ->filter(fn (array $entryPayload): bool => isset($entryPayload['customer_id']))
            ->unique('customer_id')
            ->each(function (array $entryPayload) use ($event): void {
                event(new EventWaitingRoomEntryUpdated(
                    (int) $event->id,
                    (int) $entryPayload['customer_id'],
                    $entryPayload,
                ));
            });
    }

    private function makeSummaryPayload(Event $event): array
    {
        return [
            'event_id' => $event->id,
            'waiting_count' => $this->waitingCount($event),
            'active_count' => $this->activeCount($event),
            'capacity' => $this->capacity($event),
            'updated_at' => now()->toIso8601String(),
        ];
    }

    private function capacity(Event $event): int
    {
        $totalSeats = (int) ($event->total_seats ?? 0);

        return $totalSeats > 0 ? $totalSeats : $event->seats()->count();
    }

    private function customerEntryForUpdate(Event $event, User $customer): ?EventWaitingRoomEntry
    {
        return EventWaitingRoomEntry::query()
            ->where('event_id', $event->id)
            ->where('customer_id', $customer->id)
            ->lockForUpdate()
            ->first();
    }

    private function waitingCount(Event $event): int
    {
        return EventWaitingRoomEntry::query()
            ->where('event_id', $event->id)
            ->where('status', self::STATUS_WAITING)
            ->count();
    }
}
