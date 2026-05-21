<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class EventWaitingRoomSummaryUpdated implements ShouldBroadcastNow
{
    public function __construct(
        private readonly int $eventId,
        private readonly array $payload,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("events.{$this->eventId}.waiting-room");
    }

    public function broadcastAs(): string
    {
        return 'waiting-room.summary.updated';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
