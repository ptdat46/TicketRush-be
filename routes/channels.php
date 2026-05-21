<?php

use App\Models\Event;
use App\Models\EventWaitingRoomEntry;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('events.{eventId}.waiting-room', function (User $user, int $eventId): bool {
    if ($user->role === 'admin') {
        return true;
    }

    $event = Event::query()->find($eventId);

    if (! $event) {
        return false;
    }

    if ($user->role === 'organizer') {
        return (int) $event->organizer_id === (int) $user->id;
    }

    if ($user->role !== 'customer') {
        return false;
    }

    return EventWaitingRoomEntry::query()
        ->where('event_id', $eventId)
        ->where('customer_id', $user->id)
        ->whereIn('status', ['waiting', 'active'])
        ->where('last_seen_at', '>=', now()->subSeconds(120))
        ->exists();
});

Broadcast::channel('events.{eventId}.customers.{customerId}.waiting-room', function (User $user, int $eventId, int $customerId): bool {
    if ($user->role === 'admin') {
        return true;
    }

    return $user->role === 'customer'
        && (int) $user->id === $customerId
        && EventWaitingRoomEntry::query()
            ->where('event_id', $eventId)
            ->where('customer_id', $customerId)
            ->whereIn('status', ['waiting', 'active'])
            ->where('last_seen_at', '>=', now()->subSeconds(120))
            ->exists();
});

Broadcast::channel('events.{eventId}.seats', function (User $user, int $eventId): bool {
    if ($user->role === 'admin') {
        return true;
    }

    $event = Event::query()->find($eventId);

    if (! $event) {
        return false;
    }

    if ($user->role === 'organizer') {
        return (int) $event->organizer_id === (int) $user->id;
    }

    if ($user->role !== 'customer') {
        return false;
    }

    return EventWaitingRoomEntry::query()
        ->where('event_id', $eventId)
        ->where('customer_id', $user->id)
        ->where('status', 'active')
        ->where('last_seen_at', '>=', now()->subSeconds(120))
        ->exists();
});
