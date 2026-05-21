<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class EventSeatStatusUpdated implements ShouldBroadcastNow
{
    public function __construct(
        private readonly int $eventId,
        private readonly array $payload,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("events.{$this->eventId}.seats");
    }

    public function broadcastAs(): string
    {
        return 'seat.status.updated';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
