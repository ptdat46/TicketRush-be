<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class EventWaitingRoomEntryUpdated implements ShouldBroadcastNow
{
    public function __construct(
        private readonly int $eventId,
        private readonly int $customerId,
        private readonly array $payload,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("events.{$this->eventId}.customers.{$this->customerId}.waiting-room");
    }

    public function broadcastAs(): string
    {
        return 'waiting-room.entry.updated';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
